# Domain Contracts

## Purpose

Every database-backed domain must declare its lifecycle and operations before implementation.

## Required Domain Contract

Each domain must define:

- domain name;
- source table or tables;
- aggregate root if any;
- owned entities;
- read models;
- allowed operations;
- forbidden operations;
- lifecycle statuses;
- delete policy;
- audit policy;
- idempotency policy;
- authorization policy;
- capability keys;
- invariants;
- transaction boundary;
- expected tests.

## Default CRUD Surface

Every normal master-data domain should provide:

- create;
- update/edit;
- delete;
- show;
- list.

If an operation is not allowed, the domain contract must say why.

## Delete Rule

Delete is not automatically physical delete.

Each domain must choose one:

- physical delete;
- soft delete;
- archive;
- cancel;
- void;
- forbidden.

High-risk financial, stock, audit, payment, refund, and posted transaction records must not be physically deleted unless an explicit owner decision allows it.

## Mutation Rule

Every create, update, delete, cancel, close, refund, allocate, or reversal operation must define:

- command DTO;
- result DTO;
- validation;
- transaction boundary;
- lock target if needed;
- idempotency key if retryable;
- audit event;
- repository methods;
- API capability key;
- tests.

## UI Dynamic Rule

UI must read available operations from API/capability metadata. UI must not hard-code business authority as the source of truth.

