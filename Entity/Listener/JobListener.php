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

use Doctrine\ORM\PersistentCollection;
use JMS\DiExtraBundle\Annotation as DI;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use JMS\JobQueueBundle\Entity\Job;

class JobListener
{
    public function preFlush(Job $job, PreFlushEventArgs $event)
    {
        $em = $event->getEntityManager();

        /** @var PersistentCollection $incomingDependencies */
        $incomingDependencies = $job->getIncomingDependencies();
        if ($incomingDependencies instanceof PersistentCollection && $incomingDependencies->isInitialized()) {
            foreach ($job->getIncomingDependencies() as $incomingDependency) {
                if ($em->getUnitOfWork()->getEntityState($incomingDependency) == UnitOfWork::STATE_DETACHED) {
                    $job->getIncomingDependencies()->removeElement($incomingDependency);
                    $job->getIncomingDependencies()->add($em->getReference('JMSJobQueueBundle:Job', $incomingDependency->getId()));
                }
            }
        }
    }
}