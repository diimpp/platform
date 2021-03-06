<?php

namespace Oro\Bundle\IntegrationBundle\Form\Handler;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Event\DefaultOwnerSetEvent;

class ChannelHandler
{
    const UPDATE_MARKER = 'formUpdateMarker';

    /** @var Request */
    protected $request;

    /** @var EntityManager */
    protected $em;

    /** @var FormInterface */
    protected $form;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @param Request                  $request
     * @param FormInterface            $form
     * @param EntityManager            $em
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        Request $request,
        FormInterface $form,
        EntityManager $em,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->request         = $request;
        $this->form            = $form;
        $this->em              = $em;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Process form
     *
     * @param Channel $entity
     *
     * @return bool
     */
    public function process(Channel $entity)
    {
        $userOwner   = $entity->getDefaultUserOwner();
        $isNewEntity = !$entity->getId();

        $this->form->setData($entity);

        if (in_array($this->request->getMethod(), array('POST', 'PUT'))) {
            $this->form->submit($this->request);

            if (!$this->request->get(self::UPDATE_MARKER, false) && $this->form->isValid()) {
                $this->em->persist($entity);
                $this->em->flush();

                if (!$isNewEntity && null === $userOwner && $userOwner !== $entity->getDefaultUserOwner()) {
                    $this->eventDispatcher->dispatch(DefaultOwnerSetEvent::NAME, new DefaultOwnerSetEvent($entity));
                }

                return true;
            }
        }

        return false;
    }
}
