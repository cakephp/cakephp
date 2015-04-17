<?php
namespace Cake\Controller\Component;

use Cake\ORM\Paginator as OrmPaginator;
use Cake\Network\Exception\NotFoundException;

class Paginator extends OrmPaginator {

    public $request;

    public function paginate($object, array $settings = [])
    {
        $result = parent::paginate($object, $settings);
        $pagingParams = $this->getPagingParams();

        if (!isset($this->request['paging'])) {
            $this->request['paging'] = [];
        }
        $this->request['paging'] = [$pagingParams['alias'] => $pagingParams] + (array)$this->request['paging'];

        if ($pagingParams['requestedPage'] > $pagingParams['page']) {
            throw new NotFoundException();
        }

        return $result;
    }

    public function mergeOptions($alias, $settings)
    {
        $defaults = parent::mergeOptions($alias, $settings);
        $request = array_intersect_key($this->request->query, array_flip($this->_config['whitelist']));
        return array_merge($defaults, $request);
    }
}
