<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Sylius Sp. z o.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace spec\Sylius\Bundle\CoreBundle\Doctrine\ORM\Handler;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use PhpSpec\ObjectBehavior;
use Sylius\Bundle\ResourceBundle\Controller\ResourceDeleteHandlerInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Sylius\Resource\Exception\DeleteHandlingException;
use Sylius\Resource\Model\ResourceInterface;

final class ResourceDeleteHandlerSpec extends ObjectBehavior
{
    function let(ResourceDeleteHandlerInterface $decoratedHandler, EntityManagerInterface $entityManager): void
    {
        $this->beConstructedWith($decoratedHandler, $entityManager);
    }

    function it_implements_resource_delete_handler_interface(): void
    {
        $this->shouldImplement(ResourceDeleteHandlerInterface::class);
    }

    function it_uses_decorated_handler_to_handle_resource_deletion(
        ResourceDeleteHandlerInterface $decoratedHandler,
        EntityManagerInterface $entityManager,
        RepositoryInterface $repository,
        ResourceInterface $resource,
    ): void {
        $entityManager->beginTransaction()->shouldBeCalled();
        $decoratedHandler->handle($resource, $repository)->shouldBeCalled();
        $entityManager->commit()->shouldBeCalled();

        $this->handle($resource, $repository);
    }

    function it_throws_delete_handling_exception_if_foreign_key_constraint_violation_exception_occurs_while_deleting_resource(
        ResourceDeleteHandlerInterface $decoratedHandler,
        EntityManagerInterface $entityManager,
        RepositoryInterface $repository,
        ResourceInterface $resource,
    ): void {
        $entityManager->beginTransaction()->shouldBeCalled();
        $decoratedHandler->handle($resource, $repository)->willThrow(ForeignKeyConstraintViolationException::class);
        $entityManager->commit()->shouldNotBeCalled();
        $entityManager->rollback()->shouldBeCalled();

        $this->shouldThrow(DeleteHandlingException::class)->during('handle', [$resource, $repository]);
    }

    function it_throws_delete_handling_exception_if_something_gone_wrong_with_orm_while_deleting_resource(
        ResourceDeleteHandlerInterface $decoratedHandler,
        EntityManagerInterface $entityManager,
        RepositoryInterface $repository,
        ResourceInterface $resource,
    ): void {
        /** @deprecated This fallback should be removed in Sylius 3.0 */
        if (interface_exists(ORMException::class)) {
            $ormException = new class() extends \RuntimeException implements ORMException {
            };
        } else {
            $ormException = new class() extends ORMException {
            };
        }

        $entityManager->beginTransaction()->shouldBeCalled();
        $decoratedHandler->handle($resource, $repository)->willThrow($ormException);
        $entityManager->commit()->shouldNotBeCalled();
        $entityManager->rollback()->shouldBeCalled();

        $this->shouldThrow(DeleteHandlingException::class)->during('handle', [$resource, $repository]);
    }
}
