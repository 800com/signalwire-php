<?php

use PHPUnit\Framework\TestCase;
use SignalWire\Rest\Client as SignalWireClient;
use SignalWire\Rest\Fax as SignalWireFax;
use Twilio\Rest\Fax as TwilioFax;
use Twilio\Rest\Fax\V1 as FaxV1;
use Twilio\Rest\Fax\V1\FaxContext;
use Twilio\Rest\Fax\V1\FaxInstance;
use Twilio\Rest\Fax\V1\FaxList;
use Twilio\Rest\Fax\V1\FaxPage;
use Twilio\Rest\Fax\V1\Fax\FaxMediaContext;
use Twilio\Rest\Fax\V1\Fax\FaxMediaInstance;
use Twilio\Rest\Fax\V1\Fax\FaxMediaList;
use Twilio\Rest\Fax\V1\Fax\FaxMediaPage;

/**
 * Twilio removed the Fax/V1 API from twilio/sdk v6.44+. The patches under
 * patches/add-fax-*.patch restore the classes so that SignalWire's
 * Rest\Client (which extends Twilio\Rest\Client and adds getFax()) can be
 * instantiated and the ->fax property accessed without fatal errors.
 *
 * If any patch fails to apply (e.g., the twilio/sdk Client.php drifts and
 * a context line stops matching), composer install fails and these tests
 * never run. If patches apply but with the wrong shape, these tests catch it.
 */
class FaxV1Test extends TestCase
{
    /** @dataProvider patchedClasses */
    public function testPatchedClassExists(string $class): void
    {
        $this->assertTrue(
            class_exists($class),
            "Patched class {$class} must exist after composer install applies patches",
        );
    }

    public static function patchedClasses(): array
    {
        return [
            'Fax domain'      => [TwilioFax::class],
            'Fax V1 version'  => [FaxV1::class],
            'FaxList'         => [FaxList::class],
            'FaxContext'      => [FaxContext::class],
            'FaxInstance'     => [FaxInstance::class],
            'FaxPage'         => [FaxPage::class],
            'FaxMediaContext' => [FaxMediaContext::class],
            'FaxMediaInstance'=> [FaxMediaInstance::class],
            'FaxMediaList'    => [FaxMediaList::class],
            'FaxMediaPage'    => [FaxMediaPage::class],
        ];
    }

    public function testTwilioFaxExtendsDomain(): void
    {
        $r = new ReflectionClass(TwilioFax::class);
        $this->assertSame(\Twilio\Domain::class, $r->getParentClass()->getName());
    }

    public function testTwilioFaxV1ExtendsVersion(): void
    {
        $r = new ReflectionClass(FaxV1::class);
        $this->assertSame(\Twilio\Version::class, $r->getParentClass()->getName());
    }

    public function testFaxListExtendsListResource(): void
    {
        $r = new ReflectionClass(FaxList::class);
        $this->assertSame(\Twilio\ListResource::class, $r->getParentClass()->getName());
    }

    public function testFaxInstanceExtendsInstanceResource(): void
    {
        $r = new ReflectionClass(FaxInstance::class);
        $this->assertSame(\Twilio\InstanceResource::class, $r->getParentClass()->getName());
    }

    public function testFaxContextExtendsInstanceContext(): void
    {
        $r = new ReflectionClass(FaxContext::class);
        $this->assertSame(\Twilio\InstanceContext::class, $r->getParentClass()->getName());
    }

    public function testSignalwireClientCachesFaxInstance(): void
    {
        // SignalWire\Rest\Client declares a protected $_fax cache slot so
        // getFax() can return the same instance on repeated access. If the
        // property weren't declared, PHP 8.2+ would emit a dynamic-property
        // deprecation and (more importantly) the cache would silently fail.
        // Same identity on the second access proves the slot is wired.
        $client = new SignalWireClient('project-sid', 'token', [
            'signalwireSpaceUrl' => 'example.signalwire.com',
        ]);
        $this->assertSame($client->fax, $client->fax);
    }

    public function testSignalwireClientFaxIsResolvableByMagicGetter(): void
    {
        // ->fax goes through Twilio\Domain::__get('fax'), which calls
        // getFax() if it exists on the instance. The assertion proves
        // SignalWire\Rest\Client has the getFax() method (overriding the
        // one Twilio removed in v6.38+) and returns the expected type.
        $client = new SignalWireClient('project-sid', 'token', [
            'signalwireSpaceUrl' => 'example.signalwire.com',
        ]);
        $this->assertInstanceOf(TwilioFax::class, $client->fax);
    }

    public function testFaxV1ExposesFaxesAsListResource(): void
    {
        $r = new ReflectionClass(FaxV1::class);
        $this->assertTrue($r->hasMethod('getFaxes'));

        $returnType = $r->getMethod('getFaxes')->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame(FaxList::class, $returnType->getName());
    }

    public function testSignalWireClientFaxAccessorReturnsTwilioFax(): void
    {
        $client = new SignalWireClient('project-sid', 'token', [
            'signalwireSpaceUrl' => 'example.signalwire.com',
        ]);

        $fax = $client->fax;
        $this->assertInstanceOf(SignalWireFax::class, $fax);
        $this->assertInstanceOf(TwilioFax::class, $fax);
    }

    public function testSignalwireDomainPointsAtSpace(): void
    {
        $client = new SignalWireClient('project-sid', 'token', [
            'signalwireSpaceUrl' => 'example.signalwire.com',
        ]);

        // The Fax constructor copies $client->getSignalwireDomain() into its
        // own baseUrl, so this is the value that ends up routing API calls
        // through the SignalWire space instead of fax.twilio.com.
        $this->assertSame('https://example.signalwire.com', $client->getSignalwireDomain());
    }

    public function testFaxListIsResolvableThroughClientChain(): void
    {
        $client = new SignalWireClient('project-sid', 'token', [
            'signalwireSpaceUrl' => 'example.signalwire.com',
        ]);

        $faxList = $client->fax->v1->faxes;
        $this->assertInstanceOf(FaxList::class, $faxList);
    }

    public function testFaxContextIsResolvableThroughClientChain(): void
    {
        $client = new SignalWireClient('project-sid', 'token', [
            'signalwireSpaceUrl' => 'example.signalwire.com',
        ]);

        // The Fax v1 version is invokable as a callable that returns a context.
        $context = $client->fax->v1->faxes('FX1234567890abcdef1234567890abcdef');
        $this->assertInstanceOf(FaxContext::class, $context);
    }
}
