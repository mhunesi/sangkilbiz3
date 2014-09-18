<?php

namespace core\accounting\components;

use core\accounting\models\Invoice as MInvoice;
use core\accounting\models\InvoiceDtl;
use core\purchase\models\Purchase;
use core\sales\models\Sales;
use yii\helpers\ArrayHelper;
use yii\base\UserException;

/**
 * Description of Invoice
 *
 * @author Misbahul D Munir (mdmunir) <misbahuldmunir@gmail.com>
 */
class Invoice extends \core\base\Api
{

    public static function modelClass()
    {
        return MInvoice::className();
    }

    public static function prefixEventName()
    {
        return 'e_invoice';
    }

    /**
     * 
     * @param array $data
     * @param MInvoice $model
     * @return mixed
     * @throws \Exception
     */
    public static function create($data, $model = null)
    {
        /* @var $model MInvoice */
        $model = $model ? : new MInvoice();
        $success = false;
        $model->scenario = MInvoice::SCENARIO_DEFAULT;
        $model->load($data, '');
        if (!empty($data['details'])) {
            try {
                $transaction = Yii::$app->db->beginTransaction();
                $total = 0;
                foreach ($data['details'] as $detail) {
                    $total += $detail['trans_value'];
                }
                $model->invoice_value = $total;
                static::trigger('_create', [$model]);
                $success = $model->save();
                $success = $model->saveRelated('invoiveDtls', $data, $success, 'details');
                if ($success) {
                    static::trigger('_created', [$model]);
                    $transaction->commit();
                } else {
                    $transaction->rollBack();
                    if ($model->hasRelatedErrors('invoiveDtls')) {
                        $model->addError('details', 'Details validation error');
                    }
                }
            } catch (\Exception $exc) {
                $transaction->rollBack();
                throw $exc;
            }
        } else {
            $model->validate();
            $model->addError('details', 'Details cannot be blank');
        }
        return [$success, $model];
    }

    public static function update($id, $data, $model = null)
    {
        /* @var $model MInvoice */
        $model = $model ? : static::findModel($id);
        $success = false;
        $model->scenario = MInvoice::SCENARIO_DEFAULT;
        $model->load($data, '');
        if (!isset($data['details']) || $data['details'] !== []) {
            try {
                $transaction = Yii::$app->db->beginTransaction();
                $total = 0;
                foreach ($data['details'] as $detail) {
                    $total += $detail['trans_value'];
                }
                $model->invoice_value = $total;
                static::trigger('_update', [$model]);
                $success = $model->save();
                if (!empty($data['details'])) {
                    $success = $model->saveRelated('invoiveDtls', $data, $success, 'details');
                }
                if ($success) {
                    static::trigger('_updated', [$model]);
                    $transaction->commit();
                } else {
                    $transaction->rollBack();
                    if ($model->hasRelatedErrors('invoiveDtls')) {
                        $model->addError('details', 'Details validation error');
                    }
                }
            } catch (\Exception $exc) {
                $transaction->rollBack();
                throw $exc;
            }
        } else {
            $model->validate();
            $model->addError('details', 'Details cannot be blank');
        }
        return [$success, $model];
    }

    public function createFromPurchase($data, $model = null)
    {
        $inv_vals = ArrayHelper::map($data['details'], 'id_purchase', 'value');
        $ids = array_keys($inv_vals);

        $vendors = [];
        $purchase_values = Purchase::find()
            ->where(['id_purchase' => $ids])
            ->indexBy('id_purchase')
            ->asArray()
            ->all();
        $vendor = null;
        foreach ($purchase_values as $row) {
            $vendor = $row['id_supplier'];
            $vendors[$row['id_supplier']] = true;
        }
        if (count($vendors) !== 1) {
            throw new UserException('Vendor harus sama');
        }

        $purchase_invoiced = InvoiceDtl::find()
            ->select(['id_reff', 'total' => 'sum(trans_value)'])
            ->where(['reff_type' => InvoiceDtl::TYPE_PURCHASE, 'id_reff' => $ids])
            ->groupBy('id_reff')
            ->indexBy('id_reff')
            ->asArray()
            ->all();

        $data['id_vendor'] = $vendor;
        $data['invoice_type'] = MInvoice::TYPE_IN;
        $details = [];
        foreach ($inv_vals as $id => $value) {
            $sisa = $purchase_values[$id]['purchase_value'] - $purchase_values[$id]['item_discount'];
            if (isset($purchase_invoiced[$id])) {
                $sisa -= $purchase_invoiced[$id]['total'];
            }
            if ($value > $sisa) {
                throw new UserException('Tagihan lebih besar dari sisa');
            }
            $details[] = [
                'id_reff' => $id,
                'trans_value' => $value,
            ];
        }
        $data['details'] = $details;
        return static::processOutput($success, $model);
    }

    public function createFromSales($data, $model = null)
    {
        $inv_vals = ArrayHelper::map($data['details'], 'id_sales', 'value');
        $ids = array_keys($inv_vals);

        $vendors = [];
        $sales_values = Sales::find()
            ->where(['id_sales' => $ids])
            ->indexBy('id_sales')
            ->asArray()
            ->all();
        $vendor = null;
        foreach ($purchase_values as $row) {
            $vendor = $row['id_customer'];
            $vendors[$row['id_customer']] = true;
        }
        if (count($vendors) !== 1) {
            throw new UserException('Vendor harus sama');
        }

        $sales_invoiced = InvoiceDtl::find()
            ->select(['id_reff', 'total' => 'sum(trans_value)'])
            ->where(['reff_type' => InvoiceDtl::TYPE_SALES, 'id_reff' => $ids])
            ->groupBy('id_reff')
            ->indexBy('id_reff')
            ->asArray()
            ->all();

        $data['id_vendor'] = $vendor;
        $data['invoice_type'] = MInvoice::TYPE_OUT;
        $details = [];
        foreach ($inv_vals as $id => $value) {
            $sisa = $sales_values[$id]['sales_value'] - $purchase_values[$id]['discount'];
            if (isset($sales_invoiced[$id])) {
                $sisa -= $sales_invoiced[$id]['total'];
            }
            if ($value > $sisa) {
                throw new UserException('Tagihan lebih besar dari sisa');
            }
            $details[] = [
                'id_reff' => $id,
                'trans_value' => $value,
            ];
        }
        $data['details'] = $details;
        return static::processOutput($success, $model);
    }
}