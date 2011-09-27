<?php
/**
 * File containing the ezp\Content\Field class.
 *
 * @copyright Copyright (C) 1999-2011 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace ezp\Content;
use ezp\Base\Model,
    ezp\Base\Exception\FieldValidation as FieldValidationException,
    ezp\Content\Version,
    ezp\Content\Type\FieldDefinition,
    ezp\Persistence\Content\Field as FieldVO,
    ezp\Content\FieldType\Value as FieldValue;

/**
 * This class represents a Content's field
 *
 * @property-read mixed $id
 * @property-ready string $type
 * @property \ezp\Content\FieldType\Value $value Value for current field
 * @property string $type
 * @property mixed $language
 * @property-read int $versionNo
 * @property-read mixed $fieldDefinitionId
 * @property-read \ezp\Content\Version $version
 * @property-read \ezp\Content\Type\FieldDefinition $fieldDefinition
 */
class Field extends Model
{
    /**
     * @var array Readable of properties on this object
     */
    protected $readWriteProperties = array(
        'id' => false,
        'type' => false,
        'language' => true,
        'versionNo' => false,
        'fieldDefinitionId' => false,
    );

    /**
     * @var array Dynamic properties on this object
     */
    protected $dynamicProperties = array(
        'version' => false,
        'fieldDefinition' => false,
        'value' => true
    );

    /**
     * @var \ezp\Content\Version
     */
    protected $version;

    /**
     * @var \ezp\Content\Type\FieldDefinition
     */
    protected $fieldDefinition;

    /**
     * @var \ezp\Content\FieldType\Value
     */
    protected $value;

    /**
     * Constructor, sets up properties
     *
     * @param \ezp\Content\Version $contentVersion
     * @param \ezp\Content\Type\FieldDefinition $fieldDefinition
     */
    public function __construct( Version $contentVersion, FieldDefinition $fieldDefinition )
    {
        $this->version = $contentVersion;
        $this->fieldDefinition = $fieldDefinition;
        $this->attach( $this->fieldDefinition->type, 'field/setValue' );
        $this->properties = new FieldVO(
            array(
                "type" => $fieldDefinition->fieldType,
                "fieldDefinitionId" => $fieldDefinition->id,
            )
        );
        $this->value = $fieldDefinition->defaultValue;
        $this->notify( 'field/setValue', array( 'value' => $this->value ) );
    }

    /**
     * Return content version object
     *
     * @return \ezp\Content\Version
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Return content type object
     *
     * @return \ezp\Content\Type\FieldDefinition
     */
    public function getFieldDefinition()
    {
        return $this->fieldDefinition;
    }

    /**
     * Returns current field value as FieldValue object
     *
     * @return \ezp\Content\FieldType\Value
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Assigns FieldValue object $inputValue to current field
     *
     * @param \ezp\Content\FieldType\Value $inputValue
     * @todo Make validate optional.
     */
    public function setValue( FieldValue $inputValue )
    {
        $this->value = $this->validateValue( $inputValue );
        $this->notify( 'field/setValue', array( 'value' => $inputValue ) );
    }

    /**
     * Validates $inputValue against validators registered in field definition.
     * If $inputValue is valid, it will be returned as is.
     * If not, a ValidationException will be thrown
     *
     * @todo Change so validate does not throw exceptions for logical validation errors.
     *
     * @param \ezp\Content\FieldType\FieldValue $inputValue
     * @return \ezp\Content\FieldType\FieldValue
     * @throws \ezp\Base\Exception\FieldValidation
     */
    protected function validateValue( FieldValue $inputValue )
    {
        $hasError = false;
        $errors = array();
        foreach ( $this->getFieldDefinition()->getValidators() as $validator )
        {
            if ( !$validator->validate( $inputValue ) )
            {
                $hasError = true;
                $errors = array_merge( $errors, $validator->getMessage() );
            }
        }

        if ( $hasError )
        {
            throw new FieldValidationException( $this->getFieldDefinition()->identifier, $errors );
        }

        return $inputValue;
    }
}
