<?php

namespace Oro\Bundle\SecurityBundle;

use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationProvider;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\UserBundle\Entity\User;

class SecurityFacade
{
    /**
     * @var SecurityContextInterface
     */
    private $securityContext;

    /**
     * @var AclAnnotationProvider
     */
    protected $annotationProvider;

    /**
     * @var ObjectIdentityFactory
     */
    protected $objectIdentityFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param SecurityContextInterface $securityContext
     * @param AclAnnotationProvider    $annotationProvider
     * @param ObjectIdentityFactory    $objectIdentityFactory
     * @param LoggerInterface          $logger
     */
    public function __construct(
        SecurityContextInterface $securityContext,
        AclAnnotationProvider $annotationProvider,
        ObjectIdentityFactory $objectIdentityFactory,
        LoggerInterface $logger
    ) {
        $this->securityContext       = $securityContext;
        $this->annotationProvider    = $annotationProvider;
        $this->objectIdentityFactory = $objectIdentityFactory;
        $this->logger                = $logger;
    }

    /**
     * Checks if an access to the given method of the given class is granted to the caller
     *
     * @param  string $class
     * @param  string $method
     * @return bool
     */
    public function isClassMethodGranted($class, $method)
    {
        $isGranted = true;

        // check method level ACL
        $annotation = $this->annotationProvider->findAnnotation($class, $method);
        if ($annotation !== null) {
            $this->logger->debug(
                sprintf('Check an access using "%s" ACL annotation.', $annotation->getId())
            );
            $isGranted = $this->securityContext->isGranted(
                $annotation->getPermission(),
                $this->objectIdentityFactory->get($annotation)
            );
        }

        // check class level ACL
        if ($isGranted && ($annotation === null || !$annotation->getIgnoreClassAcl())) {
            $annotation = $this->annotationProvider->findAnnotation($class);
            if ($annotation !== null) {
                $this->logger->debug(
                    sprintf('Check an access using "%s" ACL annotation.', $annotation->getId())
                );
                $isGranted = $this->securityContext->isGranted(
                    $annotation->getPermission(),
                    $this->objectIdentityFactory->get($annotation)
                );
            }
        }

        return $isGranted;
    }

    /**
     * Gets ACL annotation is bound to the given class/method
     *
     * @param string $class
     * @param string $method
     * @return Acl|null
     */
    public function getClassMethodAnnotation($class, $method)
    {
        return $this->annotationProvider->findAnnotation($class, $method);
    }

    /**
     * Checks if an access to a resource is granted to the caller
     *
     * @param string|string[] $attributes Can be a role name(s), permission name(s), an ACL annotation id,
     *                                    string in format "permission;descriptor"
     *                                    (VIEW;entity:AcmeDemoBundle:AcmeEntity, EDIT;action:acme_action)
     *                                    or something else, it depends on registered security voters
     * @param  mixed          $object     A domain object, object identity or object identity descriptor (id:type)
     *                                    (entity:Acme/DemoBundle/Entity/AcmeEntity,  action:some_action)
     *
     * @return bool
     */
    public function isGranted($attributes, $object = null)
    {
        if (is_string($attributes) && $annotation = $this->annotationProvider->findAnnotationById($attributes)) {
            if ($object === null) {
                $this->logger->debug(
                    sprintf('Check class based an access using "%s" ACL annotation.', $annotation->getId())
                );
                $isGranted = $this->securityContext->isGranted(
                    $annotation->getPermission(),
                    $this->objectIdentityFactory->get($annotation)
                );
            } else {
                $this->logger->debug(
                    sprintf('Check object based an access using "%s" ACL annotation.', $annotation->getId())
                );
                $isGranted = $this->securityContext->isGranted(
                    $annotation->getPermission(),
                    $object
                );
            }
        } elseif (is_string($object)) {
            $isGranted = $this->securityContext->isGranted(
                $attributes,
                $this->objectIdentityFactory->get($object)
            );
        } else {
            if (is_string($attributes) && $object == null) {
                $delimiter = strpos($attributes, ';');
                if ($delimiter) {
                    $object = substr($attributes, $delimiter + 1);
                    $attributes = substr($attributes, 0, $delimiter);
                }
            }

            $isGranted = $this->securityContext->isGranted($attributes, $object);
        }

        return $isGranted;
    }

    /**
     * Gets logged user object or null
     *
     * @return mixed
     */
    public function getLoggedUser()
    {
        if (null === $token = $this->securityContext->getToken()) {
            return null;
        }

        if (!is_object($user = $token->getUser())) {
            return null;
        }

        return $user;
    }

    /**
     * Gets id of currently logged in user.
     *
     * @return int 0 if there is not currently logged in user; otherwise, a number greater than zero
     */
    public function getLoggedUserId()
    {
        $user = $this->getLoggedUser();
        return $user ? $user->getId() : 0;
    }

    /**
     * Checks whether any user is currently logged in or not
     *
     * @return bool
     */
    public function hasLoggedUser()
    {
        return ($this->getLoggedUser() !== null);
    }
}
