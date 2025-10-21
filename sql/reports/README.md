# Virtuagym — Querying (Task 2)

This document explains **how** I would tackle **Task 2** of the technical assignment (description of how I would produce the three reports).

## Context

The following reports will be “simulated” over the finalized DB schema I have created for **Task 1 “Data Model”** (aka drawing the conceptual data model).

> See full schema (MySQL) in [`../schema.sql`](../schema.sql).

> ERD image: see [`../../erd/virtugym-erd.png`](../../erd/virtugym-erd.png)

## Preconditional assumptions

- `CheckIns.timestamp` and `ExerciseInstances.timestamp` are stored in **UTC** (`DATETIME(3)`).
- For monthly views, timestamps are bucketed by the club’s local time (example uses Europe/Amsterdam).
- `Subscriptions.price` is a **monthly value**.