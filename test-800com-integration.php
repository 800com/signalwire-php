#!/usr/bin/env php
<?php

/**
 * 800com SignalWire Integration Test
 *
 * This script tests all the SignalWire PHP SDK functionality used in the 800com-api
 * to ensure compatibility with PHP 8.3 and updated dependencies.
 */

require_once __DIR__ . '/vendor/autoload.php';

use SignalWire\LaML\VoiceResponse;
use SignalWire\LaML\MessagingResponse;
use SignalWire\LaML\FaxResponse;
use SignalWire\Rest\Client as SignalWireRestClient;

class SignalWire800comTest
{
    private array $results = [];
    private int $passed = 0;
    private int $failed = 0;

    public function run(): void
    {
        echo "🧪 Running 800com SignalWire Integration Tests...\n";
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "=" . str_repeat("=", 50) . "\n\n";

        // Test LaML/TwiML functionality (heavily used in your API)
        $this->testVoiceResponse();
        $this->testMessagingResponse();
        $this->testFaxResponse();

        // Test REST Client functionality
        $this->testRestClientInstantiation();
        $this->testFaxFunctionality();

        // Test specific 800com use cases
        $this->testCallRouting();
        $this->testRecording();
        $this->testSIPBridging();
        $this->testWebhookGeneration();
        $this->testGatherDTMF();
        $this->testConferencing();
        $this->testVoicemail();

        // Print results
        $this->printResults();
    }

    private function test(string $name, callable $test): void
    {
        try {
            $result = $test();
            if ($result === true || $result === null) {
                $this->results[] = "✅ $name";
                $this->passed++;
            } else {
                $this->results[] = "❌ $name - Unexpected result: " . var_export($result, true);
                $this->failed++;
            }
        } catch (Exception $e) {
            $this->results[] = "❌ $name - Exception: " . $e->getMessage();
            $this->failed++;
        }
    }

    private function testVoiceResponse(): void
    {
        $this->test('VoiceResponse instantiation', function() {
            $response = new VoiceResponse();
            return $response instanceof VoiceResponse;
        });

        $this->test('VoiceResponse Say element', function() {
            $response = new VoiceResponse();
            $response->say('Hello from 800.com!');
            $xml = (string) $response;
            return strpos($xml, '<Say>Hello from 800.com!</Say>') !== false;
        });

        $this->test('VoiceResponse Play element', function() {
            $response = new VoiceResponse();
            $response->play('https://example.com/audio.mp3');
            $xml = (string) $response;
            return strpos($xml, '<Play>https://example.com/audio.mp3</Play>') !== false;
        });

        $this->test('VoiceResponse Hangup element', function() {
            $response = new VoiceResponse();
            $response->hangup();
            $xml = (string) $response;
            return strpos($xml, '<Hangup/>') !== false;
        });

        $this->test('VoiceResponse Reject element', function() {
            $response = new VoiceResponse();
            $response->reject(['reason' => 'busy']);
            $xml = (string) $response;
            return strpos($xml, '<Reject reason="busy"/>') !== false;
        });
    }

    private function testMessagingResponse(): void
    {
        $this->test('MessagingResponse instantiation', function() {
            $response = new MessagingResponse();
            return $response instanceof MessagingResponse;
        });

        $this->test('MessagingResponse Message element', function() {
            $response = new MessagingResponse();
            $response->message('Hello from 800.com SMS!');
            $xml = (string) $response;
            return strpos($xml, '<Message>Hello from 800.com SMS!</Message>') !== false;
        });
    }

    private function testFaxResponse(): void
    {
        $this->test('FaxResponse instantiation', function() {
            $response = new FaxResponse();
            return $response instanceof FaxResponse;
        });

        $this->test('FaxResponse Receive element', function() {
            $response = new FaxResponse();
            $response->receive(['action' => 'https://example.com/fax/received']);
            $xml = (string) $response;
            return strpos($xml, 'action="https://example.com/fax/received"') !== false;
        });
    }

    private function testRestClientInstantiation(): void
    {
        $this->test('SignalWire REST Client instantiation', function() {
            // Test with dummy credentials (won't make actual API calls)
            $client = new SignalWireRestClient(
                'test-project-id',
                'test-token',
                ['signalwireSpaceUrl' => 'test.signalwire.com']
            );
            return $client instanceof SignalWireRestClient;
        });
    }

    private function testFaxFunctionality(): void
    {
        $this->test('SignalWire Fax class instantiation', function() {
            // Test with dummy credentials (won't make actual API calls)
            $client = new SignalWireRestClient(
                'test-project-id',
                'test-token',
                ['signalwireSpaceUrl' => 'test.signalwire.com']
            );

            // Test that we can access the fax property without errors
            $reflection = new \ReflectionClass($client);
            $getFaxMethod = $reflection->getMethod('getFax');
            $getFaxMethod->setAccessible(true);
            $fax = $getFaxMethod->invoke($client);

            return $fax instanceof \SignalWire\Rest\Fax;
        });

        $this->test('SignalWire Fax baseUrl functionality', function() {
            $client = new SignalWireRestClient(
                'test-project-id',
                'test-token',
                ['signalwireSpaceUrl' => 'test.signalwire.com']
            );

            $reflection = new \ReflectionClass($client);
            $getFaxMethod = $reflection->getMethod('getFax');
            $getFaxMethod->setAccessible(true);
            $fax = $getFaxMethod->invoke($client);

            // Test that the Fax domain has the correct SignalWire baseUrl
            $faxReflection = new \ReflectionClass($fax);
            $baseUrlProperty = $faxReflection->getProperty('baseUrl');
            $baseUrlProperty->setAccessible(true);
            $baseUrl = $baseUrlProperty->getValue($fax);

            return $baseUrl === 'https://test.signalwire.com';
        });
    }

    private function testCallRouting(): void
    {
        $this->test('Call routing with Dial', function() {
            $response = new VoiceResponse();
            $dial = $response->dial('', [
                'action' => 'https://api.800.com/signalwire/calls/status',
                'answerOnBridge' => true,
                'record' => 'record-from-answer-dual',
                'recordingStatusCallback' => 'https://api.800.com/signalwire/recording/upload'
            ]);
            $dial->number('+18001234567');

            $xml = (string) $response;
            return strpos($xml, '<Dial') !== false &&
                   strpos($xml, 'answerOnBridge="true"') !== false &&
                   strpos($xml, '<Number>+18001234567</Number>') !== false;
        });

        $this->test('SIP bridging', function() {
            $response = new VoiceResponse();
            $dial = $response->dial('', [
                'action' => 'https://api.800.com/signalwire/calls/status',
                'statusCallback' => 'https://api.800.com/signalwire/sip-domains/status'
            ]);
            $dial->sip('sip:bridge@800-dev.dapp.signalwire.com');

            $xml = (string) $response;
            return strpos($xml, '<Sip>sip:bridge@800-dev.dapp.signalwire.com</Sip>') !== false;
        });
    }

    private function testRecording(): void
    {
        $this->test('Recording functionality', function() {
            $response = new VoiceResponse();
            $response->record([
                'action' => 'https://api.800.com/signalwire/recording/upload',
                'maxLength' => 300,
                'finishOnKey' => '#'
            ]);

            $xml = (string) $response;
            return strpos($xml, '<Record') !== false &&
                   strpos($xml, 'maxLength="300"') !== false &&
                   strpos($xml, 'finishOnKey="#"') !== false;
        });
    }

    private function testSIPBridging(): void
    {
        $this->test('SIP domain handling', function() {
            $response = new VoiceResponse();
            $dial = $response->dial();
            $dial->sip('sip:user@800-users.sip.signalwire.com', [
                'statusCallback' => 'https://api.800.com/signalwire/sip-domains/status',
                'statusCallbackEvent' => 'initiated ringing answered completed'
            ]);

            $xml = (string) $response;
            return strpos($xml, 'sip:user@800-users.sip.signalwire.com') !== false &&
                   strpos($xml, 'statusCallbackEvent="initiated ringing answered completed"') !== false;
        });
    }

    private function testWebhookGeneration(): void
    {
        $this->test('Redirect for webhook routing', function() {
            $response = new VoiceResponse();
            $response->redirect('https://api.800.com/signalwire/calls/redirect?router=voicemail');

            $xml = (string) $response;
            return strpos($xml, '<Redirect>https://api.800.com/signalwire/calls/redirect?router=voicemail</Redirect>') !== false;
        });
    }

    private function testGatherDTMF(): void
    {
        $this->test('DTMF gathering', function() {
            $response = new VoiceResponse();
            $gather = $response->gather([
                'action' => 'https://api.800.com/signalwire/calls/dtmf',
                'input' => 'dtmf',
                'numDigits' => 1,
                'timeout' => 10
            ]);
            $gather->say('Press 1 for sales, 2 for support');

            $xml = (string) $response;
            return strpos($xml, '<Gather') !== false &&
                   strpos($xml, 'input="dtmf"') !== false &&
                   strpos($xml, 'numDigits="1"') !== false &&
                   strpos($xml, '<Say>Press 1 for sales, 2 for support</Say>') !== false;
        });
    }

    private function testConferencing(): void
    {
        $this->test('Conference functionality', function() {
            $response = new VoiceResponse();
            $dial = $response->dial('');
            $dial->conference('800com-conference-room', [
                'startConferenceOnEnter' => true,
                'endConferenceOnExit' => false,
                'record' => 'record-from-start'
            ]);

            $xml = (string) $response;
            return strpos($xml, '<Conference') !== false &&
                   strpos($xml, '800com-conference-room') !== false &&
                   strpos($xml, 'startConferenceOnEnter="true"') !== false;
        });
    }

    private function testVoicemail(): void
    {
        $this->test('Voicemail recording setup', function() {
            $response = new VoiceResponse();
            $response->say('Please leave a message after the beep.');
            $response->record([
                'action' => 'https://api.800.com/signalwire/calls/recording?voicemail=1',
                'maxLength' => 180,
                'finishOnKey' => '#',
                'playBeep' => true
            ]);
            $response->say('Thank you for your message. Goodbye.');
            $response->hangup();

            $xml = (string) $response;
            return strpos($xml, 'voicemail=1') !== false &&
                   strpos($xml, 'playBeep="true"') !== false &&
                   strpos($xml, '<Hangup/>') !== false;
        });
    }

    private function printResults(): void
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📊 TEST RESULTS\n";
        echo str_repeat("=", 60) . "\n";

        foreach ($this->results as $result) {
            echo $result . "\n";
        }

        echo "\n" . str_repeat("-", 60) . "\n";
        echo sprintf("✅ Passed: %d\n", $this->passed);
        echo sprintf("❌ Failed: %d\n", $this->failed);
        echo sprintf("📈 Success Rate: %.1f%%\n",
            $this->passed + $this->failed > 0 ? ($this->passed / ($this->passed + $this->failed)) * 100 : 0
        );

        if ($this->failed === 0) {
            echo "\n🎉 All tests passed! SignalWire PHP SDK is ready for 800com integration.\n";
            exit(0);
        } else {
            echo "\n⚠️  Some tests failed. Please review the issues above.\n";
            exit(1);
        }
    }
}

// Run the tests
$tester = new SignalWire800comTest();
$tester->run();
