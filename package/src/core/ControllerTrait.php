<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer.
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gemvc\Core;

trait ControllerTrait
{
    public function add(): void
    {
        $this->_model->postIn();

        $res = $this->_model->create($this->security->user_id);
        if ($res) {
            ApiJsonResponse::created(\count($res), $res);
        } else {
            ApiJsonResponse::badRequest($this->_model->error);
        }
    }

     /**
      * list all not deleted objects  include actives und deactivated
      * but not deleted.
      */
     public function list(): void
     {
         $res = $this->_model->list();
         // var_dump($res);
         ApiJsonResponse::success($res);
     }

    public function listall(): void
    {
        $res = $this->_model->listall();
        ApiJsonResponse::success($res);
    }

    /*#[NoReturn] public function count()
    {
        $filter = $this->_safeFilter();
        ApiJsonResponse::success($this->_model->count($filter));
    }*/

    public function id(int|string $id): void
    {
        $this->_model->single('_id', $id);
        $this->_model->_id ? ApiJsonResponse::success(1, $this->_model->single('_id', $id)) : ApiJsonResponse::badRequest('not found');
    }

    public function update(int|string $id): void
    {
        $result = Security::safePost();
        if (true === $result) {
            $this->_model->single('_id', $id);
            if ($this->_model->_id) {
                $this->_model->modified_by = $this->security->user_id;
                $this->_model->modified = time();
                if ($this->_model->update($this->security->user_id)) {
                    ApiJsonResponse::success(1, $this->_model->single('_id', $id));
                } else {
                    ApiJsonResponse::error(500, $this->_model->error);
                }
            } else {
                ApiJsonResponse::notFound(\get_class($this->_model).' with given id does not exist');
            }
        } else {
            ApiJsonResponse::badRequest('incomming POST dosent match Definition of Model');
        }
    }

    public function multiUpdate(): void
    {
    }

    /**
     * @param array $ids
     */
    public function ids(): void
    {
        $array = [];
        if (isset($_POST['ids']) && \is_array($_POST['ids'])) {
            foreach ($_POST['ids'] as $item) {
                $array[] = $item;
            }
            ApiJsonResponse::success(\count($array), $this->_model->listIds($array));
        } else {
            ApiJsonResponse::badRequest('post[ids] not found');
        }
    }

    public function active(): void
    {
        $res = $this->_model->actives();
        ApiJsonResponse::success(\count($res), $res);
    }

    public function deactives(): void
    {
        $res = $this->_model->deactives();
        ApiJsonResponse::success(\count($res), $res);
    }

    public function trash(): void
    {
        $res = $this->_model->trash();
        ApiJsonResponse::success(\count($res), $res);
    }

    public function trashall(): void
    {
        $res = $this->_model->trashall();
        ApiJsonResponse::success(\count($res), $res);
    }

    public function delete(int|string $id): void
    {
        $this->_model->single('_id', $id);
        if ($this->_model->_id) {
            if ($this->_model->delete()) {
                ApiJsonResponse::success(1, ' deleted');
            } else {
                ApiJsonResponse::internalServer('error in delete');
            }
        } else {
            ApiJsonResponse::notFound(\get_class($this->_model).' with given id does not exist');
        }
    }

    public function remove(int|string $id): void
    {
        // var_dump($id);
        $this->_model->single('_id', $id);

        if ($this->_model->_id) {
            if ($this->_model->remove($id)) {
                ApiJsonResponse::success(1, ' removed');
            } else {
                ApiJsonResponse::internalServer('error in remove');
            }
        } else {
            ApiJsonResponse::notFound(\get_class($this->_model).' with given id does not exist');
        }
    }

    public function modified(): void
    {
        $res = $this->_model->modifies();
        ApiJsonResponse::success(\count($res), $res);
    }

    public function restore($id): void
    {
        $this->_model->single('_id', $id);
        if ($this->_model->_id) {
            if ($this->_model->restore()) {
                ApiJsonResponse::success(1, ' restored');
            } else {
                ApiJsonResponse::error(500, $this->_model->error);
            }
        } else {
            ApiJsonResponse::error(404, \get_class($this->_model).' with given id does not exist');
        }
    }

    public function activate(int|string $id): void
    {
        $this->_model->single('_id', $id);
        if ($this->_model->_id) {
            if ($this->_model->activate($id)) {
                ApiJsonResponse::success(1, ' activated');
            } else {
                ApiJsonResponse::error(500, $this->_model->error);
            }
        } else {
            ApiJsonResponse::error(404, \get_class($this->_model).' with given id does not exist');
        }
    }

    public function deactivate(int|string $id): void
    {
        $this->_model->single('_id', $id);
        if ($this->_model->_id) {
            if ($this->_model->deactivate()) {
                ApiJsonResponse::success(1, ' deactivated');
            } else {
                ApiJsonResponse::error(500, $this->_model->error);
            }
        } else {
            ApiJsonResponse::error(404, \get_class($this->_model).' with given id does not exist');
        }
    }

    public function multiDelete(array $ids): false|int
    {
        // TODO
        return false;
    }

    public function multiRestore(array $ids): false|int
    {
        // TODO
        return false;
    }

    public function multiActivate(array $ids): false|int
    {
        // TODO
        return false;
    }
}
