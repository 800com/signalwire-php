<?php

use PHPUnit\Framework\TestCase;
use SignalWire\LaML\VoiceResponse;
use SignalWire\Rest\Client;
use Twilio\InstanceContext;
use Twilio\ListResource;

/**
 * Tests every signalwire-php surface the 800com-api codebase consumes.
 *
 * Audit reference (DEV-503):
 *   Rest Client:
 *     - $client->availablePhoneNumbers($country)->local
 *     - $client->availablePhoneNumbers($country)->tollFree
 *     - $client->incomingPhoneNumbers (list)
 *     - $client->incomingPhoneNumbers($sid) (context)
 *     - $client->applications (list)
 *     - $client->calls($sid) (context)
 *     - $client->calls($sid)->recordings (list)
 *   LaML VoiceResponse:
 *     - $response->dial()
 *     - $response->dial()->number(...)
 *     - $response->hangup()
 *     - $response->play(...)
 *     - $response->say(...)
 *
 * No HTTP is triggered — property/method access on the Twilio SDK is lazy,
 * and assertions stop at the returned resource type (no read/fetch/create
 * calls that would reach the network).
 */
class SignalWireUsageTest extends TestCase
{
    private const SID = 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
    private const TOKEN = 'PTXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
    private const SPACE = 'example.signalwire.com';

    private function client(): Client
    {
        return new Client(self::SID, self::TOKEN, ['signalwireSpaceUrl' => self::SPACE]);
    }

    public function testClientCanBeInstantiated(): void
    {
        $this->assertInstanceOf(Client::class, $this->client());
    }

    public function testAvailablePhoneNumbersLocalList(): void
    {
        $local = $this->client()->availablePhoneNumbers('US')->local;
        $this->assertInstanceOf(ListResource::class, $local);
    }

    public function testAvailablePhoneNumbersTollFreeList(): void
    {
        $tollFree = $this->client()->availablePhoneNumbers('US')->tollFree;
        $this->assertInstanceOf(ListResource::class, $tollFree);
    }

    public function testIncomingPhoneNumbersList(): void
    {
        $this->assertInstanceOf(ListResource::class, $this->client()->incomingPhoneNumbers);
    }

    public function testIncomingPhoneNumberContextBySid(): void
    {
        $context = $this->client()->incomingPhoneNumbers('PNxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
        $this->assertInstanceOf(InstanceContext::class, $context);
    }

    public function testApplicationsList(): void
    {
        $this->assertInstanceOf(ListResource::class, $this->client()->applications);
    }

    public function testApplicationsContextBySid(): void
    {
        $context = $this->client()->applications('APxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
        $this->assertInstanceOf(InstanceContext::class, $context);
    }

    public function testCallContextBySid(): void
    {
        $context = $this->client()->calls('CAxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
        $this->assertInstanceOf(InstanceContext::class, $context);
    }

    public function testCallRecordingsList(): void
    {
        $recordings = $this->client()->calls('CAxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx')->recordings;
        $this->assertInstanceOf(ListResource::class, $recordings);
    }

    public function testVoiceResponseSay(): void
    {
        $response = new VoiceResponse();
        $response->say('Hello');
        $this->assertStringContainsString('<Say>Hello</Say>', (string) $response);
    }

    public function testVoiceResponsePlay(): void
    {
        $response = new VoiceResponse();
        $response->play('https://example.com/audio.mp3');
        $this->assertStringContainsString(
            '<Play>https://example.com/audio.mp3</Play>',
            (string) $response,
        );
    }

    public function testVoiceResponseHangup(): void
    {
        $response = new VoiceResponse();
        $response->hangup();
        $this->assertStringContainsString('<Hangup/>', (string) $response);
    }

    public function testVoiceResponseDial(): void
    {
        $response = new VoiceResponse();
        $response->dial('+15556677888');
        $this->assertStringContainsString('<Dial>+15556677888</Dial>', (string) $response);
    }

    public function testVoiceResponseDialNumber(): void
    {
        $response = new VoiceResponse();
        $dial = $response->dial();
        $dial->number('+15556677888');
        $xml = (string) $response;
        $this->assertStringContainsString('<Dial>', $xml);
        $this->assertStringContainsString('<Number>+15556677888</Number>', $xml);
    }
}
