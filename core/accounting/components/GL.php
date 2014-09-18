<?php

namespace core\accounting\components;

use core\accounting\models\GlHeader;
use core\accounting\models\EntriSheet;
use yii\base\UserException;
use yii\base\NotSupportedException;

/**
 * Description of GL
 *
 * @author Misbahul D Munir (mdmunir) <misbahuldmunir@gmail.com>
 */
class GL extends \core\base\Api
{

    public static function modelClass()
    {
        return GlHeader::className();
    }

    public static function prefixEventName()
    {
        return 'e_gl';
    }

    public static function create($data, $model = null)
    {
        /* @var $model GlHeader */
        $model = $model ? : new GlHeader();
        $success = false;
        $model->scenario = GlHeader::SCENARIO_DEFAULT;
        $model->load($data, '');
        if (!empty($data['details'])) {
            $amount = 0;
            foreach ($data['details'] as $dataDetail) {
                $amount += $dataDetail['amount'];
            }
            if ($amount == 0) {
                try {
                    $transaction = Yii::$app->db->beginTransaction();
                    static::trigger('_create', [$model]);
                    $success = $model->save();
                    $success = $model->saveRelated('glDetails', $data, $success, 'details');
                    if ($success) {
                        static::trigger('_created', [$model]);
                        $transaction->commit();
                    } else {
                        $transaction->rollBack();
                        if ($model->hasRelatedErrors('glDetails')) {
                            $model->addError('details', 'Details validation error');
                        }
                    }
                } catch (\Exception $exc) {
                    $transaction->rollBack();
                    throw $exc;
                }
            } else {
                $model->validate();
                $model->addError('details', 'Not balance');
            }
        } else {
            $model->validate();
            $model->addError('details', 'Details cannot be blank');
        }
        return [$success, $model];
    }
    
    public static function createFromEntrysheet($data,$model=null)
    {
        $es = $data['entry_sheet'];
        if(!$es instanceof EntriSheet){
            $es = EntriSheet::findOne($es);
        }
        $values = $data['values'];
        unset($data['entry_sheet'],$data['values']);
        $details = [];
        foreach ($es->entriSheetDtls as $esDetail) {
            $nm = $esDetail->cd_esheet_dtl;
            if(isset($values[$nm])){
                $details[] = [
                    'id_coa' => $esDetail->id_coa,
                    'amount' => $values[$nm]
                ];
            }  else {
                throw new UserException("Required account \"$nm\" ");
            }
        }
        $data['details'] = $details;
        return static::processOutput($success, $model);
    }
    
    public static function update($id, $data, $model = null)
    {
        throw new NotSupportedException();
    }
    
    public static function delete($id, $model = null)
    {
        throw new NotSupportedException();
    }
}