<?php

namespace FormTools;

use JsonSchema;

class Schemas
{

    // add generic JSON validator

    /**
     * A general all-purpose schema validator. Validates a
     * @param $json string the JSON in PHP format (use json_decode(...))
     * @param $schema string the schema file in PHP format
     */
    public static function validateSchema($json, $schema)
    {
        $validator = new JsonSchema\Validator;
        $validator->validate($json, $schema);

        $is_valid = true;
        $errors = array();
        if (!$validator->isValid()) {
            $is_valid = false;
            foreach ($validator->getErrors() as $error) {
                $errors[] = $error;
            }
        }

        return array(
            "is_valid" => $is_valid,
            "errors" => $errors
        );
    }
}
