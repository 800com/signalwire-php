<?php

use PHPUnit\Framework\TestCase;
use SignalWire\Relay\Client;

abstract class BaseRelayCase extends TestCase
{
  const UUID = 'e36f227c-2946-11e8-b467-0ed5f89f718b';
  protected $client;

  protected function setUp(): void {
    $this->mockUuid();
    $this->client = new Client(array('project' => 'project', 'token' => 'token'));
    $this->client->relayProtocol = 'relay-proto';
  }

  public function tearDown(): void {
    unset($this->client);
    \Ramsey\Uuid\Uuid::setFactory(new \Ramsey\Uuid\UuidFactory());
    SignalWire\Handler::clear();
  }

  protected function mockUuid() {
    $factory = $this->createMock(\Ramsey\Uuid\UuidFactoryInterface::class);
    $factory->method('uuid4')
      ->will($this->returnValue(\Ramsey\Uuid\Uuid::fromString(self::UUID)));
    \Ramsey\Uuid\Uuid::setFactory($factory);
  }

  protected function _mockResponse($responses, $requests = []) {
    $stub = $this->createMock(SignalWire\Relay\Connection::class, ['send']);
    if (!is_array($responses)) {
      $responses = [$responses];
    }

    $stub->expects($this->exactly(count($responses)))
        ->method('send')
        ->willReturnOnConsecutiveCalls(
            ...array_map(function ($r) {
                return \React\Promise\resolve($r);
            }, $responses),
        );

    $this->client->connection = $stub;
  }

  protected function _mockSendNotToBeCalled() {
    $stub = $this->createMock(SignalWire\Relay\Connection::class, ['send']);
    $stub->expects($this->never())->method('send');
    $this->client->connection = $stub;
  }

}
