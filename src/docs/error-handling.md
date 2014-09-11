## Exceptions

Standard operations don't throw exceptions. So if you f.e. try to fetch a non-existing record false is returned.

Exceptions are only thrown if

* the underlaying curl request fails on network level
* the repository response doesn't contain a valid json structure.

You start a invalid request, which cannot succeed

* a unknown repository, content or config type is referenced
* wrong content filter options


AnyContent\Client\AnyContentClientException

## Timeout

As a default request doesn't have a timeout. With ->setTimeOut() this behaviour can get changed before any operation
(after instanciation of a client). If a request runs into a timeout a AnyContentClientException is thrown.