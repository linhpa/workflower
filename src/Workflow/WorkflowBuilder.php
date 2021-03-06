<?php
/*
 * Copyright (c) 2015 KUBO Atsuhiro <kubo@iteman.jp>,
 * All rights reserved.
 *
 * This file is part of Workflower.
 *
 * This program and the accompanying materials are made available under
 * the terms of the BSD 2-Clause License which accompanies this
 * distribution, and is available at http://opensource.org/licenses/BSD-2-Clause
 */

namespace PHPMentors\Workflower\Workflow;

use PHPMentors\Workflower\Workflow\Activity\Task;
use PHPMentors\Workflower\Workflow\Connection\SequenceFlow;
use PHPMentors\Workflower\Workflow\Event\EndEvent;
use PHPMentors\Workflower\Workflow\Event\StartEvent;
use PHPMentors\Workflower\Workflow\Gateway\ExclusiveGateway;
use Symfony\Component\ExpressionLanguage\Expression;

class WorkflowBuilder
{
    /**
     * @var array
     */
    private $endEvents = array();

    /**
     * @var array
     */
    private $exclusiveGateways = array();

    /**
     * @var string[]
     */
    private $roles = array();

    /**
     * @var array
     */
    private $sequenceFlows = array();

    /**
     * @var array
     */
    private $startEvents = array();

    /**
     * @var array
     */
    private $tasks = array();

    /**
     * @var string
     */
    private $workflowName;

    /**
     * @param string $workflowName
     */
    public function __construct($workflowName)
    {
        $this->workflowName = $workflowName;
    }

    /**
     * @param string $id
     * @param string $participant
     * @param string $name
     */
    public function addEndEvent($id, $participant, $name = null)
    {
        $this->endEvents[$id] = array($participant, $name);
    }

    /**
     * @param string $id
     * @param string $participant
     * @param string $name
     */
    public function addExclusiveGateway($id, $participant, $name = null)
    {
        $this->exclusiveGateways[$id] = array($participant, $name);
    }

    /**
     * @param string $role
     */
    public function addRole($role)
    {
        $this->roles[] = $role;
    }

    /**
     * @param string $source
     * @param string $destination
     * @param string $id
     * @param string $name
     * @param bool   $default
     * @param string $condition
     */
    public function addSequenceFlow($source, $destination, $id = null, $name = null, $default = false, $condition = null)
    {
        $this->sequenceFlows[] = array($source, $destination, $id, $name, $default, $condition);
    }

    /**
     * @param string $id
     * @param string $participant
     * @param string $name
     */
    public function addStartEvent($id, $participant, $name = null)
    {
        $this->startEvents[$id] = array($participant, $name);
    }

    /**
     * @param string $id
     * @param string $participant
     * @param string $name
     */
    public function addTask($id, $participant, $name = null)
    {
        $this->tasks[$id] = array($participant, $name);
    }

    /**
     * @return Workflow
     */
    public function build()
    {
        $workflow = new Workflow($this->workflowName);

        foreach ($this->roles as $role) {
            $workflow->addRole($role);
        }

        foreach ($this->startEvents as $id => $event) {
            list($role, $name) = $event;
            if (!$workflow->hasRole($role)) {
                throw new \LogicException();
            }

            $workflow->addFlowObject(new StartEvent($id, $role, $name));
        }

        foreach ($this->endEvents as $id => $event) {
            list($role, $name) = $event;
            if (!$workflow->hasRole($role)) {
                throw new \LogicException();
            }

            $workflow->addFlowObject(new EndEvent($id, $role, $name));
        }

        foreach ($this->tasks as $id => $task) {
            list($role, $name) = $task;
            if (!$workflow->hasRole($role)) {
                throw new \LogicException();
            }

            $workflow->addFlowObject(new Task($id, $role, $name));
        }

        foreach ($this->exclusiveGateways as $id => $gateway) {
            list($role, $name) = $gateway;
            if (!$workflow->hasRole($role)) {
                throw new \LogicException();
            }

            $workflow->addFlowObject(new ExclusiveGateway($id, $role, $name));
        }

        foreach ($this->sequenceFlows as $i => $flow) {
            list($source, $destination, $id, $name, $default, $condition) = $flow;
            if ($id === null) {
                $id = $source.'.'.$destination.$i;
            }

            $workflow->addConnectingObject(new SequenceFlow($id, $workflow->getFlowObject($source), $workflow->getFlowObject($destination), $name, $default, $condition === null ? null : new Expression($condition)));
        }

        return $workflow;
    }
}
