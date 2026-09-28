# Changelog

All notable changes to `devaspid/safi-laravel-client` will be documented in this file.

## [1.1.0] - 2026-09-28

### Added
- **`SafiPullResponder`**: Fluent responder builder for easy and rapid implementation of PULL sync endpoints (`GET /api/safi/sync`).
- **`DailySummaryAggregator`**: Helper to automatically compute standard SAFI daily summaries grouped across single or multi-branch order collections.
- **`HourlyTransactionAggregator::aggregateMultiBranch`**: Helper to calculate 24-hour transaction breakdowns for multiple branches in a single call.
- Support for `branch_code`, `channel_code`, and `branch_id` query parameters sent by SAFI Hub for single-branch filtered synchronization.

## [1.0.0] - 2026-09-27

### Added
- Initial release of SAFI Laravel Client SDK.
- Dual-mode support (Pull Provider & Push Client).
- Hourly transaction aggregation and RFM customer analysis helpers.
- Auto-retry with configurable sleep intervals and timeouts.
- Background queue job for asynchronous raw transactions dispatch.
