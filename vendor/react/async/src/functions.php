<?php

namespace React\Async;

use React\Promise\Deferred;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use function React\Promise\reject;
use function React\Promise\resolve;

/**
 * Return an async function for a function that uses [`await()`](#await) internally.
 *
 * This function is specifically designed to complement the [`await()` function](#await).
 * The [`await()` function](#await) can be considered *blocking* from the
 * perspective of the calling code. You can avoid this blocking behavior by
 * wrapping it in an `async()` function call. Everything inside this function
 * will still be blocked, but everything outside this function can be executed
 * asynchronously without blocking:
 *
 * ```php
 * Loop::addTimer(0.5, React\Async\async(function () {
 *     echo 'a';
 *     React\Async\await(React\Promise\Timer\sleep(1.0));
 *     echo 'c';
 * }));
 *
 * Loop::addTimer(1.0, function () {
 *     echo 'b';
 * });
 *
 * // prints "a" at t=0.5s
 * // prints "b" at t=1.0s
 * // prints "c" at t=1.5s
 * ```
 *
 * See also the [`await()` function](#await) for more details.
 *
 * Note that this function only works in tandem with the [`await()` function](#await).
 * In particular, this function does not "magically" make any blocking function
 * non-blocking:
 *
 * ```php
 * Loop::addTimer(0.5, React\Async\async(function () {
 *     echo 'a';
 *     sleep(1); // broken: using PHP's blocking sleep() for demonstration purposes
 *     echo 'c';
 * }));
 *
 * Loop::addTimer(1.0, function () {
 *     echo 'b';
 * });
 *
 * // prints "a" at t=0.5s
 * // prints "c" at t=1.5s: Correct timing, but wrong order
 * // prints "b" at t=1.5s: Triggered too late because it was blocked
 * ```
 *
 * As an alternative, you should always make sure to use this function in tandem
 * with the [`await()` function](#await) and an async API returning a promise
 * as shown in the previous example.
 *
 * The `async()` function is specifically designed for cases where it is used
 * as a callback (such as an event loop timer, event listener, or promise
 * callback). For this reason, it returns a new function wrapping the given
 * `$function` instead of directly invoking it and returning its value.
 *
 * ```php
 * use function React\Async\async;
 *
 * Loop::addTimer(1.0, async(function () { … }));
 * $connection->on('close', async(function () { … }));
 * $stream->on('data', async(function ($data) { … }));
 * $promise->then(async(function (int $result) { … }));
 * ```
 *
 * You can invoke this wrapping function to invoke the given `$function` with
 * any arguments given as-is. The function will always return a Promise which
 * will be fulfilled with whatever your `$function` returns. Likewise, it will
 * return a promise that will be rejected if you throw an `Exception` or
 * `Throwable` from your `$function`. This allows you to easily create
 * Promise-based functions:
 *
 * ```php
 * $promise = React\Async\async(function (): int {
 *     $browser = new React\Http\Browser();
 *     $urls = [
 *         'https://example.com/alice',
 *         'https://example.com/bob'
 *     ];
 *
 *     $bytes = 0;
 *     foreach ($urls as $url) {
 *         $response = React\Async\await($browser->get($url));
 *         assert($response instanceof Psr\Http\Message\ResponseInterface);
 *         $bytes += $response->getBody()->getSize();
 *     }
 *     return $bytes;
 * })();
 *
 * $promise->then(function (int $bytes) {
 *     echo 'Total size: ' . $bytes . PHP_EOL;
 * }, function (Exception $e) {
 *     echo 'Error: ' . $e->getMessage() . PHP_EOL;
 * });
 * ```
 *
 * The previous example uses [`await()`](#await) inside a loop to highlight how
 * this vastly simplifies consuming asynchronous operations. At the same time,
 * this naive example does not leverage concurrent execution, as it will
 * essentially "await" between each operation. In order to take advantage of
 * concurrent execution within the given `$function`, you can "await" multiple
 * promises by using a single [`await()`](#await) together with Promise-based
 * primitives like this:
 *
 * ```php
 * $promise = React\Async\async(function (): int {
 *     $browser = new React\Http\Browser();
 *     $urls = [
 *         'https://example.com/alice',
 *         'https://example.com/bob'
 *     ];
 *
 *     $promises = [];
 *     foreach ($urls as $url) {
 *         $promises[] = $browser->get($url);
 *     }
 *
 *     try {
 *         $responses = React\Async\await(React\Promise\all($promises));
 *     } catch (Exception $e) {
 *         foreach ($promises as $promise) {
 *             $promise->cancel();
 *         }
 *         throw $e;
 *     }
 *
 *     $bytes = 0;
 *     foreach ($responses as $response) {
 *         assert($response instanceof Psr\Http\Message\ResponseInterface);
 *         $bytes += $response->getBody()->getSize();
 *     }
 *     return $bytes;
 * })();
 *
 * $promise->then(function (int $bytes) {
 *     echo 'Total size: ' . $bytes . PHP_EOL;
 * }, function (Exception $e) {
 *     echo 'Error: ' . $e->getMessage() . PHP_EOL;
 * });
 * ```
 *
 * The returned promise is implemented in such a way that it can be cancelled
 * when it is still pending. Cancelling a pending promise will cancel any awaited
 * promises inside that fiber or any nested fibers. As such, the following example
 * will only output `ab` and cancel the pending [`sleep()`](https://reactphp.org/promise-timer/#sleep).
 * The [`await()`](#await) calls in this example would throw a `RuntimeException`
 * from the cancelled [`sleep()`](https://reactphp.org/promise-timer/#sleep) call
 * that bubbles up through the fibers.
 *
 * ```php
 * $promise = async(static function (): int {
 *     echo 'a';
 *     await(async(static function (): void {
 *         echo 'b';
 *         await(React\Promise\Timer\sleep(2));
 *         echo 'c';
 *     })());
 *     echo 'd';
 *
 *     return time();
 * })();
 *
 * $promise->cancel();
 * await($promise);
 * ```
 *
 * @param callable(mixed ...$args):mixed $function
 * @return callable(): PromiseInterface<mixed>
 * @since 4.0.0
 * @see coroutine()
 */
function async(callable $function): callable
{
    return static function (mixed ...$args) use ($function): PromiseInterface {
        $fiber = null;
        $promise = new Promise(function (callable $resolve, callable $reject) use ($function, $args, &$fiber): void {
            $fiber = new \Fiber(function () use ($resolve, $reject, $function, $args, &$fiber): void {
                try {
                    $resolve($function(...$args));
                } catch (\Throwable $exception) {
                    $reject($exception);
                } finally {
                    FiberMap::unregister($fiber);
                }
            });

            FiberMap::register($fiber);

            $fiber->start();
        }, function () use (&$fiber): void {
            FiberMap::cancel($fiber);
            $promise = FiberMap::getPromise($fiber);
            if ($promise instanceof PromiseInterface && \method_exists($promise, 'cancel')) {
                $promise->cancel();
            }
        });

        $lowLevelFiber = \Fiber::getCurrent();
        if ($lowLevelFiber !== null) {
            FiberMap::setPromise($lowLevelFiber, $promise);
        }

        return $promise;
    };
}


/**
 * Block waiting for the given `$promise` to be fulfilled.
 *
 * ```php
 * $result = React\Async\await($promise);
 * ```
 *
 * This function will only return after the given `$promise` has settled, i.e.
 * either fulfilled or rejected. While the promise is pending, this function
 * can be considered *blocking* from the perspective of the calling code.
 * You can avoid this blocking behavior by wrapping it in an [`async()` function](#async)
 * call. Everything inside this function will still be blocked, but everything
 * outside this function can be executed asynchronously without blocking:
 *
 * ```php
 * Loop::addTimer(0.5, React\Async\async(function () {
 *     echo 'a';
 *     React\Async\await(React\Promise\Timer\sleep(1.0));
 *     echo 'c';
 * }));
 *
 * Loop::addTimer(1.0, function () {
 *     echo 'b';
 * });
 *
 * // prints "a" at t=0.5s
 * // prints "b" at t=1.0s
 * // prints "c" at t=1.5s
 * ```
 *
 * See also the [`async()` function](#async) for more details.
 *
 * Once the promise is fulfilled, this function will return whatever the promise
 * resolved to.
 *
 * Once the promise is rejected, this will throw whatever the promise rejected
 * with. If the promise did not reject with an `Exception` or `Throwable`, then
 * this function will throw an `UnexpectedValueException` instead.
 *
 * ```php
 * try {
 *     $result = React\Async\await($promise);
 *     // promise successfully fulfilled with $result
 *     echo 'Result: ' . $result;
 * } catch (Throwable $e) {
 *     // promise rejected with $e
 *     echo 'Error: ' . $e->getMessage();
 * }
 * ```
 *
 * @param PromiseInterface $promise
 * @return mixed returns whatever the promise resolves to
 * @throws \Exception when the promise is rejected with an `Exception`
 * @throws \Throwable when the promise is rejected with a `Throwable`
 * @throws \UnexpectedValueException when the promise is rejected with an unexpected value (Promise API v1 or v2 only)
 */
function await(PromiseInterface $promise): mixed
{
    $fiber = null;
    $resolved = false;
    $rejected = false;
    $resolvedValue = null;
    $rejectedThrowable = null;
    $lowLevelFiber = \Fiber::getCurrent();

    $promise->then(
        function (mixed $value) use (&$resolved, &$resolvedValue, &$fiber, $lowLevelFiber, $promise): void {
            if ($lowLevelFiber !== null) {
                FiberMap::unsetPromise($lowLevelFiber, $promise);
            }

            if ($fiber === null) {
                $resolved = true;
                $resolvedValue = $value;
                return;
            }

            $fiber->resume($value);
        },
        function (mixed $throwable) use (&$rejected, &$rejectedThrowable, &$fiber, $lowLevelFiber, $promise): void {
            if ($lowLevelFiber !== null) {
                FiberMap::unsetPromise($lowLevelFiber, $promise);
            }

            if (!$throwable instanceof \Throwable) {
                $throwable = new \UnexpectedValueException(
                    'Promise rejected with unexpected value of type ' . (is_object($throwable) ? get_class($throwable) : gettype($throwable))
                );

                // avoid garbage references by replacing all closures in call stack.
                // what a lovely piece of code!
                $r = new \ReflectionProperty('Exception', 'trace');
                $trace = $r->getValue($throwable);

                // Exception trace arguments only available when zend.exception_ignore_args is not set
                // @codeCoverageIgnoreStart
                foreach ($trace as $ti => $one) {
                    if (isset($one['args'])) {
                        foreach ($one['args'] as $ai => $arg) {
                            if ($arg instanceof \Closure) {
                                $trace[$ti]['args'][$ai] = 'Object(' . \get_class($arg) . ')';
                            }
                        }
                    }
                }
                // @codeCoverageIgnoreEnd
                $r->setValue($throwable, $trace);
            }

            if ($fiber === null) {
                $rejected = true;
                $rejectedThrowable = $throwable;
                return;
            }

            $fiber->throw($throwable);
        }
    );

    if ($resolved) {
        return $resolvedValue;
    }

    if ($rejected) {
        throw $rejectedThrowable;
    }

    if ($lowLevelFiber !== null) {
        FiberMap::setPromise($lowLevelFiber, $promise);
    }

    $fiber = FiberFactory::create();

    return $fiber->suspend();
}

/**
 * Execute a Generator-based coroutine to "await" promises.
 *
 * ```php
 * React\Async\coroutine(function () {
 *     $browser = new React\Http\Browser();
 *
 *     try {
 *         $response = yield $browser->get('https://example.com/');
 *         assert($response instanceof Psr\Http\Message\ResponseInterface);
 *         echo $response->getBody();
 *     } catch (Exception $e) {
 *         echo 'Error: ' . $e->getMessage() . PHP_EOL;
 *     }
 * });
 * ```
 *
 * Using Generator-based coroutines is an alternative to directly using the
 * underlying promise APIs. For many use cases, this makes using promise-based
 * APIs much simpler, as it resembles a synchronous code flow more closely.
 * The above example performs the equivalent of directly using the promise APIs:
 *
 * ```php
 * $browser = new React\Http\Browser();
 *
 * $browser->get('https://example.com/')->then(function (Psr\Http\Message\ResponseInterface $response) {
 *     echo $response->getBody();
 * }, function (Exception $e) {
 *     echo 'Error: ' . $e->getMessage() . PHP_EOL;
 * });
 * ```
 *
 * The `yield` keyword can be used to "await" a promise resolution. Internally,
 * it will turn the entire given `$function` into a [`Generator`](https://www.php.net/manual/en/class.generator.php).
 * This allows the execution to be interrupted and resumed at the same place
 * when the promise is fulfilled. The `yield` statement returns whatever the
 * promise is fulfilled with. If the promise is rejected, it will throw an
 * `Exception` or `Throwable`.
 *
 * The `coroutine()` function will always return a Promise which will be
 * fulfilled with whatever your `$function` returns. Likewise, it will return
 * a promise that will be rejected if you throw an `Exception` or `Throwable`
 * from your `$function`. This allows you to easily create Promise-based
 * functions:
 *
 * ```php
 * $promise = React\Async\coroutine(function () {
 *     $browser = new React\Http\Browser();
 *     $urls = [
 *         'https://example.com/alice',
 *         'https://example.com/bob'
 *     ];
 *
 *     $bytes = 0;
 *     foreach ($urls as $url) {
 *         $response = yield $browser->get($url);
 *         assert($response instanceof Psr\Http\Message\ResponseInterface);
 *         $bytes += $response->getBody()->getSize();
 *     }
 *     return $bytes;
 * });
 *
 * $promise->then(function (int $bytes) {
 *     echo 'Total size: ' . $bytes . PHP_EOL;
 * }, function (Exception $e) {
 *     echo 'Error: ' . $e->getMessage() . PHP_EOL;
 * });
 * ```
 *
 * The previous example uses a `yield` statement inside a loop to highlight how
 * this vastly simplifies consuming asynchronous operations. At the same time,
 * this naive example does not leverage concurrent execution, as it will
 * essentially "await" between each operation. In order to take advantage of
 * concurrent execution within the given `$function`, you can "await" multiple
 * promises by using a single `yield` together with Promise-based primitives
 * like this:
 *
 * ```php
 * $promise = React\Async\coroutine(function () {
 *     $browser = new React\Http\Browser();
 *     $urls = [
 *         'https://example.com/alice',
 *         'https://example.com/bob'
 *     ];
 *
 *     $promises = [];
 *     foreach ($urls as $url) {
 *         $promises[] = $browser->get($url);
 *     }
 *
 *     try {
 *         $responses = yield React\Promise\all($promises);
 *     } catch (Exception $e) {
 *         foreach ($promises as $promise) {
 *             $promise->cancel();
 *         }
 *         throw $e;
 *     }
 *
 *     $bytes = 0;
 *     foreach ($responses as $response) {
 *         assert($response instanceof Psr\Http\Message\ResponseInterface);
 *         $bytes += $response->getBody()->getSize();
 *     }
 *     return $bytes;
 * });
 *
 * $promise->then(function (int $bytes) {
 *     echo 'Total size: ' . $bytes . PHP_EOL;
 * }, function (Exception $e) {
 *     echo 'Error: ' . $e->getMessage() . PHP_EOL;
 * });
 * ```
 *
 * @param callable(...$args):\Generator<mixed,PromiseInterface,mixed,mixed> $function
 * @param mixed ...$args Optional list of additional arguments that will be passed to the given `$function` as is
 * @return PromiseInterface<mixed>
 * @since 3.0.0
 */
function coroutine(callable $function, mixed ...$args): PromiseInterface
{
    try {
        $generator = $function(...$args);
    } catch (\Throwable $e) {
        return reject($e);
    }

    if (!$generator instanceof \Generator) {
        return resolve($generator);
    }

    $promise = null;
    $deferred = new Deferred(function () use (&$promise) {
        if ($promise instanceof PromiseInterface && \method_exists($promise, 'cancel')) {
            $promise->cancel();
        }
        $promise = null;
    });

    /** @var callable $next */
    $next = function () use ($deferred, $generator, &$next, &$promise) {
        try {
            if (!$generator->valid()) {
                $next = null;
                $deferred->resolve($generator->getReturn());
                return;
            }
        } catch (\Throwable $e) {
            $next = null;
            $deferred->reject($e);
            return;
        }

        $promise = $generator->current();
        if (!$promise instanceof PromiseInterface) {
            $next = null;
            $deferred->reject(new \UnexpectedValueException(
                'Expected coroutine to yield ' . PromiseInterface::class . ', but got ' . (is_object($promise) ? get_class($promise) : gettype($promise))
            ));
            return;
        }

        $promise->then(function ($value) use ($generator, $next) {
            $generator->send($value);
            $next();
        }, function (\Throwable $reason) use ($generator, $next) {
            $generator->throw($reason);
            $next();
        })->then(null, function (\Throwable $reason) use ($deferred, &$next) {
            $next = null;
            $deferred->reject($reason);
        });
    };
    $next();

    return $deferred->promise();
}

/**
 * @param iterable<callable():PromiseInterface<mixed,Exception>> $tasks
 * @return PromiseInterface<array<mixed>,Exception>
 */
function parallel(iterable $tasks): PromiseInterface
{
    $pending = [];
    $deferred = new Deferred(function () use (&$pending) {
        foreach ($pending as $promise) {
            if ($promise instanceof PromiseInterface && \method_exists($promise, 'cancel')) {
                $promise->cancel();
            }
        }
        $pending = [];
    });
    $results = [];
    $continue = true;

    $taskErrback = function ($error) use (&$pending, $deferred, &$continue) {
        $continue = false;
        $deferred->reject($error);

        foreach ($pending as $promise) {
            if ($promise instanceof PromiseInterface && \method_exists($promise, 'cancel')) {
                $promise->cancel();
            }
        }
        $pending = [];
    };

    foreach ($tasks as $i => $task) {
        $taskCallback = function ($result) use (&$results, &$pending, &$continue, $i, $deferred) {
            $results[$i] = $result;
            unset($pending[$i]);

            if (!$pending && !$continue) {
                $deferred->resolve($results);
            }
        };

        $promise = \call_user_func($task);
        assert($promise instanceof PromiseInterface);
        $pending[$i] = $promise;

        $promise->then($taskCallback, $taskErrback);

        if (!$continue) {
            break;
        }
    }

    $continue = false;
    if (!$pending) {
        $deferred->resolve($results);
    }

    return $deferred->promise();
}

/**
 * @param iterable<callable():PromiseInterface<mixed,Exception>> $tasks
 * @return PromiseInterface<array<mixed>,Exception>
 */
function series(iterable $tasks): PromiseInterface
{
    $pending = null;
    $deferred = new Deferred(function () use (&$pending) {
        if ($pending instanceof PromiseInterface && \method_exists($pending, 'cancel')) {
            $pending->cancel();
        }
        $pending = null;
    });
    $results = [];

    if ($tasks instanceof \IteratorAggregate) {
        $tasks = $tasks->getIterator();
        assert($tasks instanceof \Iterator);
    }

    /** @var callable():void $next */
    $taskCallback = function ($result) use (&$results, &$next) {
        $results[] = $result;
        $next();
    };

    $next = function () use (&$tasks, $taskCallback, $deferred, &$results, &$pending) {
        if ($tasks instanceof \Iterator ? !$tasks->valid() : !$tasks) {
            $deferred->resolve($results);
            return;
        }

        if ($tasks instanceof \Iterator) {
            $task = $tasks->current();
            $tasks->next();
        } else {
            $task = \array_shift($tasks);
        }

        $promise = \call_user_func($task);
        assert($promise instanceof PromiseInterface);
        $pending = $promise;

        $promise->then($taskCallback, array($deferred, 'reject'));
    };

    $next();

    return $deferred->promise();
}

/**
 * @param iterable<callable(mixed=):PromiseInterface<mixed,Exception>> $tasks
 * @return PromiseInterface<mixed,Exception>
 */
function waterfall(iterable $tasks): PromiseInterface
{
    $pending = null;
    $deferred = new Deferred(function () use (&$pending) {
        if ($pending instanceof PromiseInterface && \method_exists($pending, 'cancel')) {
            $pending->cancel();
        }
        $pending = null;
    });

    if ($tasks instanceof \IteratorAggregate) {
        $tasks = $tasks->getIterator();
        assert($tasks instanceof \Iterator);
    }

    /** @var callable $next */
    $next = function ($value = null) use (&$tasks, &$next, $deferred, &$pending) {
        if ($tasks instanceof \Iterator ? !$tasks->valid() : !$tasks) {
            $deferred->resolve($value);
            return;
        }

        if ($tasks instanceof \Iterator) {
            $task = $tasks->current();
            $tasks->next();
        } else {
            $task = \array_shift($tasks);
        }

        $promise = \call_user_func_array($task, func_get_args());
        assert($promise instanceof PromiseInterface);
        $pending = $promise;

        $promise->then($next, array($deferred, 'reject'));
    };

    $next();

    return $deferred->promise();
}
