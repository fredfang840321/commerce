<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\commerce\migrations;

use Craft;
use craft\commerce\elements\Variant;
use craft\db\Migration;
use craft\db\Query;

/**
 * m190528_161915_description_on_purchasable migration.
 */
class m190528_161915_description_on_purchasable extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%commerce_purchasables}}', 'description')) {
            $this->addColumn('{{%commerce_purchasables}}', 'description', $this->text());
        }

        $variantIds = (new Query())
            ->select(['id'])
            ->from(['{{%commerce_variants}}'])
            ->column();

        $productTypes = (new Query())
            ->select([
                'id',
                'descriptionFormat',
            ])
            ->from(['{{%commerce_producttypes}} producttypes'])
            ->indexBy('id')
            ->all();

        if (!empty($productTypes)) {
            foreach ($variantIds as $variantId) {
                $variant = Variant::find()->id($variantId)->one();

                if ($variant) {
                    $productTypeId = (new Query())
                        ->select(['[[products.typeId]]'])
                        ->from(['{{%commerce_products}} products'])
                        ->leftJoin('{{%commerce_variants}} variants', '[[variants.productId]] = [[products.id]]')
                        ->where('[[variants.id]] = ' . $variantId)
                        ->scalar();

                    if (array_key_exists($productTypeId, $productTypes)) {
                        $productTypeDescriptionFormat = $productTypes[$productTypeId]['descriptionFormat'];
                        try {
                            $description = Craft::$app->getView()->renderObjectTemplate($productTypeDescriptionFormat, $variant);

                            $this->update('{{%commerce_purchasables}}', ['description' => $description], ['id' => $variantId]);
                        } catch (\Exception $e) {
                            // A Re-save or variants will update the purchasable descriptions - so don't worry about it.
                        }
                    }
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190528_161915_description_on_purchasable cannot be reverted.\n";
        return false;
    }
}
