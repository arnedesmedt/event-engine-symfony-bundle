<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\TestHelper;

use Closure;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use EventEngine\Messaging\MessageBag;
use Zenstruck\Messenger\Test\Transport\TestTransport;

use function assert;
use function count;
use function is_array;
use function is_string;
use function sprintf;

/**
 * @method assertCount(int $count, iterable $haystack, string $message = '')
 * @method assertEquals($expected, $actual, string $message = '')
 */
trait MessengerAssertions
{
    /** @param array<array{className: class-string<JsonSchemaAwareRecord>, payload: array<string, mixed>|Closure}|class-string<JsonSchemaAwareRecord>> $expectedMessages */
    public function assertQueuedMessages(array $expectedMessages): Closure
    {
        return function (TestTransport $transport) use ($expectedMessages): void {
            $queuedMessages = $transport->queue()->messages(MessageBag::class);

            $this->assertCount(
                count($expectedMessages),
                $queuedMessages,
                sprintf(
                    "Expected '%d' queued messages, but got '%d'.",
                    count($expectedMessages),
                    count($queuedMessages),
                ),
            );

            foreach ($queuedMessages as $index => $queuedMessage) {
                assert($queuedMessage instanceof MessageBag);

                $expectedMessage = $expectedMessages[$index];
                $expectedMessageClass = is_string($expectedMessage)
                    ? $expectedMessage
                    : $expectedMessage['className'];

                $this->assertEquals(
                    $expectedMessageClass,
                    $queuedMessage->messageName(),
                    sprintf(
                        "Message class '%s' at index %d does not match expected class '%s'",
                        $queuedMessage->messageName(),
                        $index,
                        $expectedMessageClass,
                    ),
                );

                if (! is_array($expectedMessage)) {
                    continue;
                }

                $expectedPayload = $expectedMessage['payload'];

                if ($expectedPayload instanceof Closure) {
                    $expectedPayload($queuedMessage->payload());

                    continue;
                }

                $this->assertEquals(
                    $expectedPayload,
                    $queuedMessage->payload(),
                    sprintf(
                        "Message payload with class '%s' at index %d does not match the expected payload",
                        $queuedMessage->messageName(),
                        $index,
                    ),
                );
            }
        };
    }
}
