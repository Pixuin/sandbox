<?php

namespace IdeaMaker\Facades;

use Nette\Application\AbortException;
use Nette\ArrayHash;
use Nette\Database\Connection;
use Nette\Diagnostics\Debugger;
use Nette\Security\User;

class StaticPageFacade extends Facade
{
    protected $languages;
    public function __construct($languages, Connection $db, User $user)
    {
        parent::__construct($db, $user); // TODO: Change the autogenerated stub
        $this->languages = $languages;
    }

    protected function beforeSave(ArrayHash &$data)
    {

        parent::beforeSave($data);
    }


    public function getLangTable() {
        return $this->connection->table('staticPage_lang');
    }

    public function hasChildren($parentId)
    {
        return (bool) $this->getTable()->where(array('parent_id'=>$parentId))->count('id');
    }

    public function save(ArrayHash $data)
    {


        $this->connection->beginTransaction();
        try {
            $data['parent_id'] = $data['parent_id'] ?:NULL;
            $data['id'] = $data['id'] ?:NULL;
            $localizedData = (array) $data;
            if (isset($data['id'])) {
                $defaultData['id'] = $data['id'];
            }
            $defaultData['parent_id'] = $data['parent_id'];
            $defaultData['order'] = $data['order'];
            $defaultData['is_active'] = $data['is_active'];

            unset($localizedData['parent_id']); // unset default data

            foreach ($this->languages as $language) {
                $localizedData[$language]['staticPage_id'] = $localizedData['id'];
                $localizedData[$language]['lang'] = $language;
                foreach(array('keywords', 'description', 'title', 'slug', 'content') as $column) {
                    $localizedData[$language][$column] = $localizedData[$column.'_'.$language];
                }
            }

            $this->prepareCreatedAt($defaultData);
            $this->prepareUpdatedAt($defaultData);
            $this->prepareCreatedBy($defaultData);
            $this->prepareUpdatedBy($defaultData);
            if (isset($data['id'])) { // update
                $this->connection->table('staticPage')->where(array('id'=>$data['id']))->update($defaultData);
                foreach($this->languages as $language) {
                    $result = $this->connection->table('staticPage_lang')->where(array('staticPage_id'=>$data['id'], 'lang'=>$language))->update($localizedData[$language]);
//                    Debugger::barDump($defaultData, 'default data');
//                    Debugger::barDump($localizedData, 'lcalized data');
//                    exit;
                }
            } else { // insert

                $localizedData['id'] = $data['id'] = $this->connection->table('staticPage')->insert($defaultData)->id;

                if ($localizedData['id']) {
                    foreach ($this->languages as $language) {
                        $localizedData[$language]['staticPage_id'] = $localizedData['id'];
                        $this->connection->table('staticPage_lang')->insert($localizedData[$language]);
                    }
                } else {
                    throw new AbortException;
                }
            }
            $this->connection->commit();
        }catch (\Exception $e) {
            $this->connection->rollBack();
            Debugger::barDump($e);
//            exit;
            throw $e;
        }


        return $data['id'];
    }

    public function delete($id)
    {
        return $this->getTable()->find($id)->delete();
    }


    public function getSlugByIdAndLang($id, $lang) {
        $data = $this->getLangTable()->select('slug')->where('staticPage_id', $id)->where('lang', $lang)->fetch();
        return  $data ? $data->slug:'';
    }

    public function getTitleByIdAndLang($id, $lang) {
        $data = $this->getLangTable()->select('title')->where('staticPage_id', $id)->where('lang', $lang)->fetch();
        return  $data ? $data->title:'';
    }



}