# Changelog

## 4.0.0 (2022-07-11)

A major new feature release, see [**release announcement**](https://clue.engineering/2022/announcing-reactphp-async).

*   We'd like to emphasize that this component is production ready and battle-tested.
    We plan to support all long-term support (LTS) releases for at least 24 months,
    so you have a rock-solid foundation to build on top of.

*   The v4 release will be the way forward for this package. However, we will still
    actively support v3 and v2 to provide a smooth upgrade path for those not yet
    on PHP 8.1+. If you're using an older PHP version, you may use either version
    which all provide a compatible API but may not take advantage of newer language
    features. You may target multiple versions at the same time to support a wider range of
    PHP versions:

    * [`4.x` branch](https://github.com/reactphp/async/tree/4.x) (PHP 8.1+)
    * [`3.x` branch](https://github.com/reactphp/async/tree/3.x) (PHP 7.1+)
    * [`2.x` branch](https://github.com/reactphp/async/tree/2.x) (PHP 5.3+)

This update involves some major new features and a minor BC break over the
`v3.0.0` release. We've tried hard to avoid BC breaks where possible and
minimize impact otherwise. We expect that most consumers of this package will be
affected by BC breaks, but updating should take no longer than a few minutes.
See below for more details:

*   Feature / BC break: Require PHP 8.1+ and add `mixed` type declarations.
    (#14 by @clue)

*   Feature: Add Fiber-based `async()` and `await()` functions.
    (#15, #18, #19 and #20 by @WyriHaximus and #26, #28, #30, #32, #34, #55 and #57 by @clue)

*   Project maintenance, rename `main` branch to `4.x` and update installation instructions.
    (#29 by @clue)

The following changes had to be ported to this release due to our branching
strategy, but also appeared in the `v3.0.0` release:

*   Feature: Support iterable type for `parallel()` + `series()` + `waterfall()`.
    (#49 by @clue)

*   Feature: Forward compatibility with upcoming Promise v3.
    (#48 by @clue)

*   Minor documentation improvements.
    (#36 by @SimonFrings and #51 by @nhedger)

## 3.0.0 (2022-07-11)

See [`3.x` CHANGELOG](https://github.com/reactphp/async/blob/3.x/CHANGELOG.md) for more details.

## 2.0.0 (2022-07-11)

See [`2.x` CHANGELOG](https://github.com/reactphp/async/blob/2.x/CHANGELOG.md) for more details.

## 1.0.0 (2013-02-07)

* First tagged release
