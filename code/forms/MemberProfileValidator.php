<?php
/**
 * This validator provides the unique and required functionality for {@link MemberProfileField}s.
 *
 * @package silverstripe-memberprofiles
 */
class MemberProfileValidator extends RequiredFields {

	protected $fields, $member, $unique = array();

	/**
	 * @param MemberProfileField[] $fields
	 * @param Member $member
	 */
	public function __construct($fields, $member = null) {
		parent::__construct();

		$this->fields = $fields;
		$this->member = $member;

		foreach($this->fields as $field) {
			if($field->Required) $this->addRequiredField($field->MemberField);
			if($field->Unique)   $this->unique[] = $field->MemberField;
		}

		if($member && $member->ID && $member->Password) {
			$this->removeRequiredField('Password');
		}
	}

	/**
	 * JavaScript validation is disabled on profile forms.
	 */
	public function javascript() {
		return null;
	}

	public function php($data) {
		$member = $this->member;
		$valid  = true;

		foreach($this->unique as $field) {
			$other = DataObject::get_one (
				'Member',
				sprintf('"%s" = \'%s\'', Convert::raw2sql($field), Convert::raw2sql($data[$field]))
			);

			$isEmail = $field === 'Email';
			$emailOK = !$isEmail;
			if ($isEmail) {
				$existing = Member::get()->filter('Email:nocase', $data['Email']);

				// This ensures the existing member isn't the same as the current member, in case they're updating information.

				if($current = Member::currentUserID()) {
					$existing = $existing->filter('ID:not', $current);
				}
				$emailOK = !$existing->first();
			}
			if ($other && (!$this->member || !$this->member->exists() || $other->ID != $this->member->ID) || !$emailOK) {
				$fieldInstance = $this->form->Fields()->dataFieldByName($field);

				if($fieldInstance->getCustomValidationMessage()) {
					$message = $fieldInstance->getCustomValidationMessage();
				} else {
					$message = sprintf (
						_t('MemberProfiles.MEMBERWITHSAME', 'There is already a member with the same %s.'),
						$field
					);
				}

				$valid = false;
				$this->validationError($field, $message, 'required');
			}
		}

		return $valid && parent::php($data);
	}

}
