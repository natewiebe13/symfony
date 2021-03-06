<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\PasswordHasher\Tests\Hasher;

use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Security\Core\User\LegacyPasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;

class UserPasswordHasherTest extends TestCase
{
    use ExpectDeprecationTrait;

    /**
     * @group legacy
     */
    public function testHashWithNonPasswordAuthenticatedUser()
    {
        $this->expectDeprecation('Since symfony/password-hasher 5.3: Returning a string from "getSalt()" without implementing the "Symfony\Component\Security\Core\User\LegacyPasswordAuthenticatedUserInterface" interface is deprecated, the "%s" class should implement it.');

        $userMock = $this->createMock('Symfony\Component\Security\Core\User\UserInterface');
        $userMock->expects($this->any())
            ->method('getSalt')
            ->willReturn('userSalt');

        $mockHasher = $this->createMock(PasswordHasherInterface::class);
        $mockHasher->expects($this->any())
            ->method('hash')
            ->with($this->equalTo('plainPassword'), $this->equalTo('userSalt'))
            ->willReturn('hash');

        $mockPasswordHasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $mockPasswordHasherFactory->expects($this->any())
            ->method('getPasswordHasher')
            ->with($this->equalTo($userMock))
            ->willReturn($mockHasher);

        $passwordHasher = new UserPasswordHasher($mockPasswordHasherFactory);

        $encoded = $passwordHasher->hashPassword($userMock, 'plainPassword');
        $this->assertEquals('hash', $encoded);
    }

    public function testHash()
    {
        $userMock = $this->createMock(TestPasswordAuthenticatedUser::class);
        $userMock->expects($this->any())
            ->method('getSalt')
            ->willReturn('userSalt');

        $mockHasher = $this->createMock(PasswordHasherInterface::class);
        $mockHasher->expects($this->any())
            ->method('hash')
            ->with($this->equalTo('plainPassword'), $this->equalTo('userSalt'))
            ->willReturn('hash');

        $mockPasswordHasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $mockPasswordHasherFactory->expects($this->any())
            ->method('getPasswordHasher')
            ->with($this->equalTo($userMock))
            ->willReturn($mockHasher);

        $passwordHasher = new UserPasswordHasher($mockPasswordHasherFactory);

        $encoded = $passwordHasher->hashPassword($userMock, 'plainPassword');
        $this->assertEquals('hash', $encoded);
    }

    public function testVerify()
    {
        $userMock = $this->createMock(TestPasswordAuthenticatedUser::class);
        $userMock->expects($this->any())
            ->method('getSalt')
            ->willReturn('userSalt');
        $userMock->expects($this->any())
            ->method('getPassword')
            ->willReturn('hash');

        $mockHasher = $this->createMock(PasswordHasherInterface::class);
        $mockHasher->expects($this->any())
            ->method('verify')
            ->with($this->equalTo('hash'), $this->equalTo('plainPassword'), $this->equalTo('userSalt'))
            ->willReturn(true);

        $mockPasswordHasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $mockPasswordHasherFactory->expects($this->any())
            ->method('getPasswordHasher')
            ->with($this->equalTo($userMock))
            ->willReturn($mockHasher);

        $passwordHasher = new UserPasswordHasher($mockPasswordHasherFactory);

        $isValid = $passwordHasher->isPasswordValid($userMock, 'plainPassword');
        $this->assertTrue($isValid);
    }

    public function testNeedsRehash()
    {
        $user = new User('username', null);
        $hasher = new NativePasswordHasher(4, 20000, 4);

        $mockPasswordHasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $mockPasswordHasherFactory->expects($this->any())
            ->method('getPasswordHasher')
            ->with($user)
            ->will($this->onConsecutiveCalls($hasher, $hasher, new NativePasswordHasher(5, 20000, 5), $hasher));

        $passwordHasher = new UserPasswordHasher($mockPasswordHasherFactory);

        $user->setPassword($passwordHasher->hashPassword($user, 'foo', 'salt'));
        $this->assertFalse($passwordHasher->needsRehash($user));
        $this->assertTrue($passwordHasher->needsRehash($user));
        $this->assertFalse($passwordHasher->needsRehash($user));
    }
}

abstract class TestPasswordAuthenticatedUser implements LegacyPasswordAuthenticatedUserInterface, UserInterface
{
}
