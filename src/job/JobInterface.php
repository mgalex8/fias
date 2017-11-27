<?php

namespace marvin255\fias\job;

use marvin255\fias\pipe\FlowInterface;

/**
 * Интерфейс для объекта, который выполняет какую-либо задачу внутри очереди.
 *
 * Служит для того, чтобы выпонить какую-либо задачу и передать полученные
 * данные далее. Например, сначала нам нужно получить ссылку из SOAP (первая задача),
 * затем скачать архив (вторая задача), затем распаковать (третья задача) и т.д.
 * Каждая задача должна быть реализована в отдельном объекте с данным интерфейсом
 * и зарегистрирована в общей очереди.
 */
interface JobInterface
{
    /**
     * Служит для запуска данной задачи. Прнимает на вход объект,
     * который хранит и передает результаты обработки задач далее. Возвращает
     * bool того удалось выполнить все работы или нет.
     *
     * @param \marvin255\fias\pipe\FlowInterface $flow
     *
     * @return bool
     */
    public function run(FlowInterface $flow);
}
