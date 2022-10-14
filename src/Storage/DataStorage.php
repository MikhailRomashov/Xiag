<?php

namespace App\Storage;

use App\Model;

//CodeReview : работу с таблицами можно было сделать через модели и по возможности с кеширование 
class DataStorage
{
    /**
     * @var \PDO 
     */
    public $pdo;

    public function __construct()
    {
        $this->pdo = new \PDO('mysql:dbname=task_tracker;host=127.0.0.1', 'root','root');
    }

    /**
     * @param int $projectId
     * @throws Model\NotFoundException
     */
//CodeReview :  жетко определить тип $projectId
    public function getProjectById($projectId)
    {
        //CodeReview : упростить написание запроса без лишних типизаций
        //CodeReview :переделать на prepare с передачей параметра через execute
        $stmt = $this->pdo->query('SELECT * FROM project WHERE id = ' . (int) $projectId);

        if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return new Model\Project($row);
        }

        throw new Model\NotFoundException();
    }

    /**
     * @param int $project_id
     * @param int $limit
     * @param int $offset
     */
    //CodeReview :  жетко определить типы всех входных параметров
    public function getTasksByProjectId(int $project_id, $limit, $offset)
    {
        //CodeReview :для запросов с параметрами необходимо использовать prepare
        $stmt = $this->pdo->query("SELECT * FROM task WHERE project_id = $project_id LIMIT ?, ?");
        
        //CodeReview :во-первых передать все три параметра через execute
        //CodeReview :во-вторых  $limit,$offset  перепутаны местами
        //CodeReview :в-третьих передача параметров через execute приводит к тому что они превращатся в тип string и
        //CodeReview :потребуется дополнительные преобразования в тип int для переменных $offset,$limit иначе будет ошибка
        //CodeReview :если исключена опасность sql-иньекции то есть вариант упростить и сразу их вставить в строку запроса
        $stmt->execute([$limit, $offset]);
        
        //CodeReview : убрать ненужную инициалтзация пустого массива tasks
        $tasks = [];
        foreach ($stmt->fetchAll() as $row) {
            $tasks[] = new Model\Task($row);
        }

        return $tasks;
    }

    /**
     * @param array $data
     * @param int $projectId
     * @return Model\Task
     */
    
    //CodeReview : жетко определить типы всех входных параметров
    public function createTask(array $data, $projectId)
    {
        $data['project_id'] = $projectId;

        $fields = implode(',', array_keys($data));
        $values = implode(',', array_map(function ($v) {
            return is_string($v) ? '"' . $v . '"' : $v;
        }, $data));
        
//CodeReview : переделать под prepare /execute
        $this->pdo->query("INSERT INTO task ($fields) VALUES ($values)");
        $data['id'] = $this->pdo->query('SELECT MAX(id) FROM task')->fetchColumn();

        return new Model\Task($data);
    }
}
