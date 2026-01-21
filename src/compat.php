<?php

namespace React\Promise;

// CancellablePromiseInterface and ExtendedPromiseInterface extend PromiseInterface and were removed in v3.
// These aliases make existing code compatible with the newest React\Promise.
class_alias(PromiseInterface::class, CancellablePromiseInterface::class);
class_alias(PromiseInterface::class, ExtendedPromiseInterface::class);
