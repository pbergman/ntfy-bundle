# ntfy bundle

A symfony bundle that makes it easy to manage multiple [nfty](https://ntfy.sh) servers and topics.

When creating services from the config and autowire is enabled there will parameter binds registered for the server and every topic which can be used arguments.  

So for example we have the following config:

```yaml
framework:
  http_client:
    scoped_clients:
      example.client:
        base_uri: 'https://ntfy.sh'
        auth_bearer: 'XXXXXXXXXXXXX'

p_bergman_ntfy:
  servers:
    example:
      client: example.client
      topics:
        - foo
        - bar
```
the following binds will be registered:

```injectablephp
PBergman\\Bundle\\NtfyBundle\\Api\\StaticTopicClient $exampleFooNtfyClient
PBergman\\Bundle\\NtfyBundle\\Api\\StaticTopicClient $exampleBarNtfyClient
PBergman\\Ntfy\\Api\\Client                          $exampleNtfyClient

```

And can be used for example if a controller want to publish on foo a message:

```injectablephp

public function indexController(Request $request, StaticTopicClient $exampleFooNtfyClient) :Response
{
    $exampleFooNtfyClient->publish(null, 'hello');

```



Use the

```injectablephp
    php bin/console config:dump-reference p_bergman_ntfy
```

Command to see latest configuration options.


