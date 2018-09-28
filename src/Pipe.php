<?php

declare(strict_types=1);

namespace marvin255\fias;

use marvin255\fias\task\Task;
use marvin255\fias\task\RuntimeException;
use marvin255\fias\state\State;
use Exception;

/**
 * Основной объект приложения, который запускает зарегистрированные операции на
 * выполнение.
 */
class Pipe
{
    /**
     * @var \marvin255\fias\task\Task[]
     */
    private $tasks = [];

    /**
     * Регистрирует операцию в приложении.
     *
     * @param \marvin255\fias\task\Task $task
     *
     * @return $this
     */
    public function pipe(Task $task): self
    {
        $this->tasks[] = $task;

        return $this;
    }

    /**
     * Запускает все операции на выполнение.
     *
     * @param \marvin255\fias\state\State $state
     *
     * @return $this
     *
     * @throws \marvin255\fias\task\RuntimeException
     */
    public function run(State $state): self
    {
        foreach ($this->tasks as $task) {
            try {
                $task->run($state);
            } catch (Exception $e) {
                throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
            }
            if ($state->isCompleted()) {
                break;
            }
        }

        return $this;
    }
}