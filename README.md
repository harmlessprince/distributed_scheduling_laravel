# Laravel Distributed Task Processing: Evolution from Naive to High-Performance

Welcome to **Laravel Distributed Processing**! This repository documents my journey of building and evolving a real-world background processing system—taking it from a fragile, single-process prototype to a resilient, high-throughput, multi-worker distributed processing architecture.

Using intuitive real-world analogies and clear technical explanations, I have documented how I evolved the codebase across **5 distinct phases**.

---

## The Core Problem I Set Out to Solve

Imagine building a financial system that must continuously process customer proposals, issue payment cards via a third-party banking API, and attach those cards back to the proposals.

- **Input:** Proposals in the database marked as `ELIGIBLE`.
- **Action:** Fetch the proposal, call an external service for card creation, save the card details, and link the card ID to the proposal.
- **Output:** Proposal status updated to `ELIGIBLE_WITH_ATTACHED_CARD`.

While simple on the surface, scaling this to handle thousands or millions of records concurrently without crashing the server, locking the database, or double-issuing cards required careful architectural decisions. Here is how I solved it step-by-step.

---

## The Evolution Across 5 Phases

```
+-----------------------------------------------------------------------------------+
|  Phase 1: Naive Monolith         ---> Load all records into memory at once        |
|  Phase 2: Naive Batching         ---> Chunk records, but lock DB during HTTP calls |
|  Phase 3: Short Transactions     ---> Isolate chunks with pessimistic FOR UPDATE  |
|  Phase 4: Concurrency Enabled    ---> Non-blocking processing via SKIP LOCKED     |
|  Phase 5: Bulk Ingestion         ---> Batch DB inserts & CASE updates for max speed|
+-----------------------------------------------------------------------------------+
```

---

### Phase 1: The Heavy Suitcase (Naive Monolith)
> **Branch:** [`phase1`](../../tree/phase1)

#### The Analogy
Imagine trying to pack 10,000 household items into a single giant suitcase and carrying it down the street yourself in one trip. If the suitcase breaks halfway through, you drop everything on the pavement.

#### What My Initial Code Did
- A scheduled cron job ran every minute.
- It fetched **all** eligible proposals from the database in a single query (`Proposal::where('status', 'ELIGIBLE')->get()`).
- It looped through each record one by one, calling the external card API and writing to the database.

#### Why It Failed
1. **Memory Blowout (Out of Memory):** Loading tens of thousands of Eloquent models into PHP memory at once quickly exceeded PHP's memory limit.
2. **Timeout Collisions:** Processing thousands of external HTTP requests sequentially took minutes. If PHP's execution time limit didn't kill it, the next minute's scheduled cron job started a *second* copy of the job.
3. **Double Processing:** Because both job instances looked for `ELIGIBLE` proposals, they simultaneously grabbed and processed the exact same records—issuing duplicate cards.

---

### Phase 2: The Mega Checkout Line (Naive Batching + Giant Transaction)
> **Branch:** [`phase2`](../../tree/phase2)

#### The Analogy
Instead of carrying one giant suitcase, I packed items into smaller boxes of 50. However, I stood at the grocery store checkout counter with all 1,000 boxes at once, holding up the line while calling suppliers over the phone to verify each item before ringing it up.

#### What I Changed
- Introduced batching/chunking using a `while(true)` loop to fetch 50 proposals at a time.
- Wrapped the entire processing loop in a single, massive `DB::transaction(...)`.

#### Why It Failed
1. **Long DB Transaction & Lock Hostage:** Holding a database transaction open while waiting for third-party HTTP responses locked database connections for too long, starving other site visitors.
2. **All-or-Nothing Rollback Disaster:** If record #499 failed due to a transient network glitch, the entire transaction rolled back—reverting all 498 previously processed proposals and wasting all the external card requests already made.

---

### Phase 3: The Traffic Jam (Short Transactions + `FOR UPDATE` Locking)
> **Branch:** [`phase3`](../../tree/phase3)

#### The Analogy
Now I paid box-by-box at the counter. But to make sure no one else touched my items, I closed off the entire highway lane behind me. Other delivery trucks arrived, but they were forced to stop and wait in a bumper-to-bumper queue behind my truck.

#### What I Changed
- Moved `DB::transaction(...)` *inside* the loop so each 50-item chunk got its own short, independent transaction.
- Introduced pessimistic row locking: `Proposal::where(...)->lockForUpdate()->limit(50)->get()`.

#### Progress & Remaining Bottlenecks
- **Progress:** If a chunk failed, only that 50-item batch rolled back. Database transactions closed in milliseconds.
- **Bottleneck (Thread Blocking):** Standard `FOR UPDATE` forced concurrent worker processes to *wait* for locked rows to be released. If 5 background workers ran at the same time, Workers 2 through 5 froze in place waiting for Worker 1 to finish, wasting worker capacity.

---

### Phase 4: Multi-Lane Express Highway (`SKIP LOCKED` + Observability)
> **Branch:** [`phase4`](../../tree/phase4)

#### The Analogy
Instead of closing the highway lane, my trucks entered a multi-lane express toll plaza. When Worker Truck 1 claimed Boxes 1–50, Worker Truck 2 instantly saw those boxes were claimed and **skipped right past them** to take Boxes 51–100 without waiting a single millisecond.

#### What I Changed
- Upgraded the query lock from `FOR UPDATE` to `FOR UPDATE SKIP LOCKED` (`->lock('FOR UPDATE SKIP LOCKED')`).
- Integrated **Laravel Telescope** to monitor live SQL query executions and measure database performance under heavy loads.

#### Why It Succeeded
1. **True Parallel Processing:** Multiple background workers can safely process tasks simultaneously without stepping on each other's toes or waiting in line.
2. **Zero Row Collisions:** `SKIP LOCKED` guarantees that no two workers ever fetch or process the same proposal record.
3. **Graceful Worker Scaling:** I can spin up 10, 20, or 50 queue workers effortlessly, and throughput scales linearly.

---

### Phase 5: Supercharged Bulk Freight (Batch Ingestion & Updates)
> **Branch:** [`phase5`](../../tree/phase5)

#### The Analogy
Instead of walking up to the mailbox 50 times to drop off 50 individual letters, I dropped off one single pre-sorted container holding all 50 letters at the post office loading dock.

#### What I Changed
- Replaced 1-by-1 Eloquent database writes inside the chunk loop with **bulk batch operations**.
- Used `CardService::createCards()` to insert 50 cards in **one single SQL statement** (`DB::table('cards')->insert(...)`).
- Used a single SQL `CASE` statement query to update all 50 proposal statuses and link their card IDs in **one single SQL UPDATE**.

#### Performance Impact
- **Before (Phase 4):** 50 SELECTs/INSERTs/UPDATEs per batch = **100+ database network roundtrips**.
- **After (Phase 5):** 1 SELECT + 1 Bulk INSERT + 1 Bulk UPDATE = **3 total database roundtrips** per batch!

---

## Summary Architecture Matrix

| Phase | Batching | DB Lock Strategy | Transaction Boundary | Concurrency | DB Roundtrips / 50 Items | Resilience Level |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Phase 1** | None (All) | None | None | Vulnerable to Race Conditions | 100+ | Low (Fails on scale) |
| **Phase 2** | 50 items | None | Monolithic Loop | Holds DB connection open | 100+ | Low (Rollback hazard) |
| **Phase 3** | 50 items | `FOR UPDATE` | Per Batch | Serial (Workers block each other) | 100+ | Medium (Safe but slow) |
| **Phase 4** | 50 items | `SKIP LOCKED` | Per Batch | Fully Parallel (Non-blocking) | 100+ | High (Enterprise ready) |
| **Phase 5** | 50 items | `SKIP LOCKED` | Per Batch | Fully Parallel + High Throughput | **3** | Maximum Performance |

---

## How to Explore My Codebase

You can check out each branch to inspect the exact code changes I made during each evolution phase:

```bash
# Phase 1: Naive monolith
git checkout phase1

# Phase 2: Naive chunking with giant transaction
git checkout phase2

# Phase 3: Short transactions with FOR UPDATE
git checkout phase3

# Phase 4: Parallel processing with SKIP LOCKED + Telescope
git checkout phase4

# Phase 5: Bulk database operations & maximum throughput
git checkout phase5
```

---

## Key Lessons Learned

1. **Avoid `->get()` on Unbounded Queries:** Always chunk or paginate background operations to protect server memory.
2. **Never Put Network/API Calls Inside DB Transactions:** Keep database transactions as short as possible; external HTTP calls can delay or fail unpredictably.
3. **Use `SKIP LOCKED` for Distributed Queues/Workers:** If multiple background workers pick up tasks from a database table, `SKIP LOCKED` is essential for lock-free parallel execution.
4. **Bulk Operations Beat Loops:** Executing 1 multi-row SQL query is exponentially faster than executing 50 individual single-row queries in a loop.
