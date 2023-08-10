<?php

namespace SciloneToolboxBundle\PHPUnit;

use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\Constraint\Constraint;

trait PHPUnitTrait
{
    public function withConsecutive(array $firstCallArguments, array ...$consecutiveCallsArguments): iterable
    {
        $this->assertSameSizeForEachArguments($firstCallArguments, $consecutiveCallsArguments);

        $allConsecutiveCallsArguments = [
            $firstCallArguments,
            ...$consecutiveCallsArguments
        ];
        $numberOfArguments            = count($firstCallArguments);
        $argumentList                 = $this->getArgumentList($allConsecutiveCallsArguments, $numberOfArguments);

        $mockedMethodCall = 0;
        $callbackCall     = 0;
        foreach ($argumentList as $index => $argument) {
            yield $this->createCallback($argumentList, $mockedMethodCall, $callbackCall, $index, $numberOfArguments);
        }
    }

    private function assertSameSizeForEachArguments(array $firstCallArguments, array $consecutiveCallsArguments): void
    {
        foreach ($consecutiveCallsArguments as $consecutiveCallArguments) {
            self::assertSameSize(
                $firstCallArguments,
                $consecutiveCallArguments,
                'Each expected arguments list need to have the same size.'
            );
        }
    }

    private function getArgumentList(array $allConsecutiveCallsArguments, int $numberOfArguments): array
    {
        $argumentList = [];
        for ($argumentPosition = 0; $argumentPosition < $numberOfArguments; $argumentPosition ++) {
            $argumentList[$argumentPosition] = array_column($allConsecutiveCallsArguments, $argumentPosition);
        }

        return $argumentList;
    }

    private function createCallback(
        array $argumentList,
        int &$mockedMethodCall,
        int &$callbackCall,
        int $index,
        int $numberOfArguments
    ): Callback {
        return new Callback(
            static function (mixed $actualArgument) use (
                $argumentList,
                &$mockedMethodCall,
                &$callbackCall,
                $index,
                $numberOfArguments
            ): bool {
                $expected = $argumentList[$index][$mockedMethodCall] ?? null;
                $callbackCall++;
                $mockedMethodCall = (int) ($callbackCall / $numberOfArguments);

                if ($expected instanceof Constraint) {
                    self::assertThat($actualArgument, $expected);
                } else {
                    self::assertEquals($expected, $actualArgument);
                }
                return true;
            }
        );
    }
}
