<?php

namespace LimeSurvey\Models\Services;

class QuestionAttributeHelper
{
    /**
     * Merges the 'base' attributes (ex: core question attributes) with the extended question attributes
     * (ex: question theme attributes). It also removes all attributes where extended attribute's inputType is
     * empty.
     * If an extended attribute's name cannot be determined, it's omitted.
     *
     * @param array $baseAttributes    the base set of attributes
     * @param array $extendedAttributes    the attributes to merge into the base set
     *
     * @return array the merged attributes
     */
    public function mergeQuestionAttributes($baseAttributes, $extendedAttributes)
    {
        $attributes = $baseAttributes;
        foreach ($extendedAttributes as $attribute) {
            // Omit the attribute if it has no name.
            // This shouldn't happen if sanitizeQuestionAttributes() is used.
            if (!isset($attribute['name'])) {
                continue;
            }

            $attributeName = $attribute['name'];
            $inputType = $attribute['inputtype'];
            // remove attribute if inputtype is empty
            if (empty($inputType)) {
                unset($attributes[$attributeName]);
            } else {
                $customAttribute = array_merge(
                    \QuestionAttribute::getDefaultSettings(),
                    $attribute
                );
                $attributes[$attributeName] = $customAttribute;
            }
        }
        return $attributes;
    }

    /**
     * Sanitizes an array of question attributes.
     * Current tasks:
     *  - makes sure that attributes have a name (removes them if name cannot be determined)
     *  - replaces empty arrays (generally resulting from empty xml nodes) with null.
     *
     * @param array $attributes the array of attributes to sanitize
     *
     * @return array<string,array> the array of sanitized attributes
     */
    public function sanitizeQuestionAttributes($attributes)
    {
        /** @var array<string,array> An array of sanitized question attributes */
        $sanitizedAttributes = [];
        foreach ($attributes as $key => $attribute) {
            // Make sure the attribute has a name.
            if (!is_numeric($key)) {
                $attribute['name'] = $key;
            } else {
                if (!isset($attribute['name'])) {
                    continue;
                }
            }

            // Replace empty arrays with null
            foreach ($attribute as $propertyName => $propertyValue) {
                if ($propertyValue === []) {
                    $attribute[$propertyName] = null;
                }
            }

            // Make sure "options" have the expected structure
            if (isset($attribute['options']['option']) && is_array($attribute['options']['option'])) {
                $attribute['options'] = $attribute['options']['option'];
            }

            $sanitizedAttributes[$attribute['name']] = $attribute;
        }
        return $sanitizedAttributes;
    }

    /**
     * Returns the received array of attributes filled with the values specified, taking into account the
     * 'i18n' property of the attributes.
     *
     * Both this and rewriteQuestionAttributeArray() are helper methods and accomplish quite similar tasks,
     * but the output is different: rewriteQuestionAttributeArray returns a name -> value array, while here
     * we return a complete definition map and the value as a piece of information mingled into it.
     *
     * @param array $attributes the attributes to be filled
     * @param array $attributeValues the values for the attributes
     * @param array $languages the languages to use for i18n attributes
     *
     * @return array the same source attributes with their corresponding values (when available)
     */
    public function fillAttributesWithValues($attributes, $attributeValues, $languages = [])
    {
        foreach ($attributes as $key => $attribute) {
            if ($attribute['i18n'] == false) {
                if (isset($attributeValues[$attribute['name']][''])) {
                    $attributes[$key]['value'] = $attributeValues[$attribute['name']][''];
                } else {
                    $attributes[$key]['value'] = $attribute['default'];
                }
                // Sanitize value in case it's saved as empty array
                if ($attributes[$key]['value'] === []) {
                    $attributes[$key]['value'] = null;
                }
            } else {
                foreach ($languages as $language) {
                    if (isset($attributeValues[$attribute['name']][$language])) {
                        $attributes[$key][$language]['value'] = $attributeValues[$attribute['name']][$language];
                    } else {
                        $attributes[$key][$language]['value'] = $attribute['default'];
                    }
                    // Sanitize value in case it's saved as empty array
                    if ($attributes[$key][$language]['value'] === []) {
                        $attributes[$key][$language]['value'] = null;
                    }
                }
            }
        }
        return $attributes;
    }

    /**
     * Receives an array of question attributes and groups them by category.
     * Used by advanced settings widget.
     *
     * @param array $attributes
     * @return array Grouped question attributes, with category as array key
     */
    public function groupAttributesByCategory($attributes)
    {
        $attributesByCategory = [];
        foreach ($attributes as $attribute) {
            $attributesByCategory[$attribute['category']][] = $attribute;
        }
        return $attributesByCategory;
    }

    /**
     * Returns the question attributes added by plugins ('newQuestionAttributes' event) for
     * the specified question type.
     *
     * @param string $questionType     the question type to retrieve the attributes for.
     *
     * @return array    the question attributes added by plugins
     */
    public function getAttributesFromPlugin($questionType)
    {
        $allPluginAttributes = \QuestionAttribute::getOwnQuestionAttributesViaPlugin();
        if (empty($allPluginAttributes)) {
            return [];
        }

        // Filter to get this question type setting
        $questionTypeAttributes = $this->filterAttributesByQuestionType($allPluginAttributes, $questionType);

        // Complete category if missing
        $questionTypeAttributes = $this->fillMissingCategory($questionTypeAttributes, gT('Plugin'));

        $questionTypeAttributes = $this->sanitizeQuestionAttributes($questionTypeAttributes);

        return $questionTypeAttributes;
    }

    /**
     * Filters an array of question attribute definitions by question type
     *
     * @param array $attributes    array of question attribute definitions to filter
     * @param string $questionType the question type that the attributes should apply to
     *
     * @return array    an array containing only the question attributes that match the specified question type
     */
    public function filterAttributesByQuestionType($attributes, $questionType)
    {
        $questionTypeAttributes = array_filter($attributes, function ($attribute) use ($questionType) {
            return $this->attributeAppliesToQuestionType($attribute, $questionType);
        });

        return $questionTypeAttributes;
    }

    /**
     * Check if question attribute applies to a specific question type
     *
     * @param array $attribute     question attribute definition
     * @param string $questionType the question type that the attribute should apply to
     *
     * @return bool     returns true if the question attribute applies to the specified question type
     */
    public function attributeAppliesToQuestionType($attribute, $questionType)
    {
        return isset($attribute['types']) && stripos($attribute['types'], $questionType) !== false;
    }

    /**
     * Makes sure all the question attributes in an array have a category. If an attribute's
     * category is missing, it's filled with the specified category name.
     *
     * @param array $attributes    array of question attribute definitions
     * @param string $sCategoryName the category name to use if an attribute doesn't have one
     *
     * @return array    returns the array attributes with Category field complete
     */
    public function fillMissingCategory($attributes, $categoryName)
    {
        foreach ($attributes as &$attribute) {
            if (empty($attribute['category'])) {
                $attribute['category'] = $categoryName;
            }
        }
        return $attributes;
    }

    /**
     * This function returns an array of the attributes for the particular question
     * including their values set in the database
     *
     * @param \Question $question  The question object
     * @param string|null $language If you give a language then only the attributes for that language are returned
     * @param string|null $questionThemeOverride   Name of the question theme to use instead of the question's current theme
     * @param boolean $advancedOnly If set to true, only the advanced attributes will be returned
     * @return array
     */
    public function getQuestionAttributesWithValues($question, $language = null, $questionThemeOverride = null, $advancedOnly = false)
    {
        $survey = $question->survey;
        if (empty($survey)) {
            throw new \Exception('This question has no survey - qid = ' . json_encode($question->qid));
        }

        // Get attribute values
        $attributeValues = $this->getAttributesValuesFromDB($question->qid);

        // Get question theme name if not specified
        $questionTheme = !empty($attributeValues['question_template']['']) ? $attributeValues['question_template'][''] : 'core';
        $questionTheme = !empty($questionThemeOverride) ? $questionThemeOverride : $questionTheme;

        // Get advanced attribute definitions for the question type
        $questionTypeAttributes = $this->getAttributesFromQuestionType($question->type, $advancedOnly);

        // Get question theme attribute definitions
        $questionThemeAttributes = $this->getAttributesFromQuestionTheme($questionTheme, $question->type);

        // Merge the attributes with the question theme ones
        $attributes = $this->mergeQuestionAttributes($questionTypeAttributes, $questionThemeAttributes);

        // Get question attributes from plugins ('newQuestionAttributes' event)
        $pluginAttributes = $this->getAttributesFromPlugin($question->type);
        $attributes = $this->mergeQuestionAttributes($attributes, $pluginAttributes);

        uasort($attributes, 'categorySort');

        // Fill attributes with values
        $languages = is_null($language) ? $survey->allLanguages : [$language];
        $attributes = $this->fillAttributesWithValues($attributes, $attributeValues, $languages);

        return $attributes;
    }

    /**
     * Get all saved attribute values for one question as array
     *
     * @param int $questionId  the question id
     * @return array the returning array structure will be like
     *               array(attributeName => array(languageCode => value, ...), ...)
     *               where languageCode is '' if no language is specified.
     */
    public function getAttributesValuesFromDB($questionId)
    {
        return \QuestionAttribute::model()->getAttributesAsArrayFromDB($questionId);
    }

    /**
     * Get the definitions of question attributes from Question Theme
     *
     * @param string $questionTheme    the name of the question theme
     * @param string $questionType     the base question type
     * @return array    all question attribute definitions provided by the question theme
     */
    public function getAttributesFromQuestionTheme($questionTheme, $questionType)
    {
        return \QuestionTheme::getAdditionalAttrFromExtendedTheme($questionTheme, $questionType);
    }

    /**
     * Get the definitions of question attributes from base question type
     *
     * @param string $questionType     the base question type
     * @param boolean $advancedOnly     if true, general attributes ('question_template', 'gid', ...) are excluded
     * @return array    all question attribute definitions provided by the question type
     */
    public function getAttributesFromQuestionType($questionType, $advancedOnly = false)
    {
        return \QuestionAttribute::getQuestionAttributesSettings($questionType, $advancedOnly);
    }
}
