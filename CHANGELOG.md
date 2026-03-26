# Changelog

All notable changes to this library are documented in this file.

## [Unreleased]

- **Breaking:** Upgrade `react/promise` to version 3.3.0, dropping
  compatibility with version 2 (#60)

## [0.3.0] - 2026-03-24

- Add strict type declarations (#58, #65)
- Support PHP 8.5 (#57)

## [0.2.0] - 2025-11-18

- **Breaking:** Raise minimum PHP version to 8.2 (#55)
- Add timezone support to `RRule` via a constructor argument and a
  `setTimezone()` setter (#53)
- Extend `RRule` to normalize recurrence datetimes to the configured
  timezone and handle start/end date conversions on timezone change (#55)

## [0.1.2] - 2023-09-21

- Fix `RRule` serialized result omitting the configured timezone (#38)
- Fix serialized datetime format to include the timezone name, and stop
  resetting the start date to UTC on deserialization (#42)
- Fix `Cron` and `OneOff` timezone handling (#46)

## [0.1.1] - 2023-05-15

- Suggest `ext-ev` PHP extension for improved event loop performance (#32)
- Fix `UNTIL` is now used instead of `DTEND` in the expression (#35)
- Fix `DTSTART` not included in the `RRULE` expression (#39)
- Several fixes to enhance timezone support (#37, #41)

## [0.1.0] - 2023-03-29

Initial release providing cron-based (`Cron`), iCal recurrence-based (`RRule`),
and one-off (`OneOff`) task scheduling in a ReactPHP event loop.

[Unreleased]: https://github.com/Icinga/ipl-scheduler/compare/v0.3.0...HEAD
[0.3.0]: https://github.com/Icinga/ipl-scheduler/releases/tag/v0.3.0
[0.2.0]: https://github.com/Icinga/ipl-scheduler/releases/tag/v0.2.0
[0.1.2]: https://github.com/Icinga/ipl-scheduler/releases/tag/v0.1.2
[0.1.1]: https://github.com/Icinga/ipl-scheduler/releases/tag/v0.1.1
[0.1.0]: https://github.com/Icinga/ipl-scheduler/releases/tag/v0.1.0
