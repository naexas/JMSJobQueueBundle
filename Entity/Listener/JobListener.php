<?php

/*
 * Copyright (c) 2015, ranQuest Pte. Ltd., Singapore.
 *
 * This unpublished source code is proprietary to ranQuest.
 * All rights reserved. The methods and techniques described herein
 * are considered trade secrets and/or confidential.
 * Reproduction or distribution, in whole or in part, is forbidden.
 */

namespace JMS\JobQueueBundle\Entity\Listener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use JMS\DiExtraBundle\Annotation as DI;
use JMS\JobQueueBundle\Entity\Job;

class JobListener
{
    /**
     * @param Job               $job
     * @param PreFlushEventArgs $event
     */
    public function preFlush(Job $job, PreFlushEventArgs $event)
    {
        $em = $event->getEntityManager();

        $this->fixDetachedOwner($job, $em);
        $this->fixDetachedDependencies($job, $em);
        $this->fixDetachedIncomingDependencies($job, $em);
    }

    /**
     * @param Job           $job
     * @param EntityManager $em
     */
    private function fixDetachedOwner(Job $job, EntityManager $em)
    {
        $owner = $job->getOwner();
        if ($owner && $em->getUnitOfWork()->getEntityState($owner) == UnitOfWork::STATE_DETACHED) {
            $owner = $owner->getId() ? $em->getReference('JMSJobQueueBundle:Job', $owner->getId()) : null;
            $job->setOwner($owner);
        }
    }

    /**
     * @param Job           $job
     * @param EntityManager $em
     */
    private function fixDetachedDependencies(Job $job, EntityManager $em)
    {
        $dependencies = $job->getDependencies();
        if ($dependencies instanceof PersistentCollection && !$dependencies->isInitialized()) {
            return;
        }

        foreach ($dependencies as $dependency) {
            if ($em->getUnitOfWork()->getEntityState($dependency) == UnitOfWork::STATE_DETACHED) {
                $job->getDependencies()->removeElement($dependency);
                $job->getDependencies()->add($em->getReference('JMSJobQueueBundle:Job', $dependency->getId()));
            }
        }
    }

    /**
     * @param Job           $job
     * @param EntityManager $em
     */
    private function fixDetachedIncomingDependencies(Job $job, EntityManager $em)
    {
        $incomingDependencies = $job->getIncomingDependencies();
        if ($incomingDependencies instanceof PersistentCollection && !$incomingDependencies->isInitialized()) {
            return;
        }

        foreach ($incomingDependencies as $incomingDependency) {
            if ($em->getUnitOfWork()->getEntityState($incomingDependency) == UnitOfWork::STATE_DETACHED) {
                $job->getIncomingDependencies()->removeElement($incomingDependency);
                $job->getIncomingDependencies()->add($em->getReference('JMSJobQueueBundle:Job', $incomingDependency->getId()));
            }
        }
    }
}