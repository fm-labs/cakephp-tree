<?php
declare(strict_types=1);

namespace Tree\Controller;

use Cake\Core\Exception\Exception;
use Cake\Http\Exception\NotFoundException;

/**
 * Class TreeSortControllerTrait
 * @package Tree\Controller
 *
 * @property string $modelClass
 */
trait TreeSortControllerTrait
{
    public function tree_sort()
    {

        $this->autoRender = false;
        $this->response->type('text');

        $data = [];
        try {
            if (!$this->modelClass) {
                throw new NotFoundException("No model found");
            }

            $Model = $this->loadModel($this->modelClass);
            if (!$Model->hasBehavior('Tree') || !is_a($Model->behaviors()->get('Tree'), '\\Tree\\Model\\Behavior\\TreeBehavior')) {
                throw new Exception("Model has no Tree.Tree behavior attached");
            }

            if (!$this->request->is('post')) {
                throw new Exception("Invalid request method", 400);
            }

            $raw = $this->request->getData();
            $id = $raw['id'] ?? null;
            $after = $raw['after'] ?? 0;

            if (!$id) {
                throw new Exception("Invalid param: Missing param 'id'", 400);
            }

            $Model->id = $id;
            $data['id'] = $id;
            $data['after'] = $after;
            $data['success'] = $Model->moveAfterId($after);
            $data['status'] = 200;
        } catch (Exception $ex) {
            $data['status'] = $ex->getCode();
            $data['error'] = $ex->getMessage();
        }

        $this->response->statusCode($data['status']);
        $this->response->body(json_encode($data));
    }
}
