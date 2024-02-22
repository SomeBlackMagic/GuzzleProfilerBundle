# Configuration Reference

##### bundles
```php
SomeBlackMagic\GuzzleProfilerBundle\GuzzleProfilerBundle::class => ['dev' => true, 'test' => true],
```

##### Packages Configuration

```yaml
guzzle_profiler:
    # (de)activate logging; default: %kernel.debug%
    logging: true

    # (de)activate profiler; default: %kernel.debug%
    profiling: true
```

##### Service Configuration
```yaml

  guzzle.handler-stack:
    public: true
    class: GuzzleHttp\HandlerStack
    factory: [ GuzzleHttp\HandlerStack, 'create' ]
    calls:
      - [ 'push', [ '@guzzle.retry.middleware', 'retry'] ]
      - [ 'push', [ '@guzzle.rewind.middleware', 'rewind'] ]
      - [ 'push', [ '@guzzle.log.middleware', 'log'] ]
      #add this block to handler-stack
      - [ 'push', [ '@guzzle_profile.middleware.profile', 'guzzle_profile'] ]
      - [ 'push', [ '@guzzle_profile.middleware.log', 'guzzle_profile_log'] ]
      - [ 'push', [ '@guzzle_profiler.middleware.request_time', 'guzzle_profile_request_time'] ]

  guzzle_profile.middleware.profile:
    class: Closure
    factory: [ '@guzzle_profiler.middleware.profile', profile ]

  guzzle_profile.middleware.log:
    class: Closure
    factory: [ '@guzzle_profiler.middleware.log', log ]

```
