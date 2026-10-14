# StockWise — Inventory Management Website

StockWise is a PHP + MySQL inventory management website packaged with Docker Compose.

## Features you can test

- Create, read, update, and delete products (CRUD).
- Search by product name, SKU, or category.
- Filter products by low stock or safe stock.
- Increase/decrease stock with buttons; stock cannot go below zero.
- Automatic low-stock / out-of-stock status based on the minimum threshold.
- Dashboard metrics: product count, total units, low-stock count, and estimated inventory value.
- Unique SKU validation and basic server-side input validation.

## Requirements

- Docker Desktop or Docker Engine
- Docker Compose v2

## Start

```bash
docker compose up --build -d
```

Open http://localhost:8080

Useful checks:

```bash
docker compose ps
docker compose logs --tail=100 web db
```

## Stop

```bash
docker compose down
```

The database volume is retained. To remove the local database data too, run `docker compose down -v` (destructive).

## Suggested functional test checklist

1. Open the homepage and confirm demo products are shown.
2. Add a product with SKU `TEST-001`; verify it appears in the list and dashboard counts update.
3. Try adding another product with the same SKU; verify the duplicate is rejected.
4. Search for `TEST-001`, then clear the search.
5. Filter by `Stok menipis`; verify products at or below their threshold appear.
6. Increase and decrease stock using `+` and `−`; verify counts update and stock cannot go below zero.
7. Edit the test product and change its category, price, or threshold.
8. Delete the test product and confirm it disappears.
9. Run `docker compose ps` and capture evidence that web and db services are running.

## Configuration and data

- `.env.example` documents environment variables. Copy it to `.env` to customize local credentials.
- Do not commit `.env` or real secrets.
- `database/init.sql` creates the table and inserts demo products when MySQL initializes a **new** data volume. It does not rerun automatically on an existing volume.

## Important assignment note

If the course assignment requires an existing project rather than a newly created application, confirm with your instructor before submitting this starter as the selected project. Otherwise, use it for practice and apply the Dockerization work to an approved existing repository.
