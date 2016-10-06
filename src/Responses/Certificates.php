<?php

namespace Xolphin\Responses;

class Certificates extends Base {
    /** @var Certificate[]  */
    public $certificates = [];

    /**
     * Certificates constructor.
     * @param object $data
     */
    function __construct($data) {
        parent::__construct($data);

        if(!$this->isError()) {
            if(!empty($this->_embedded->certificates))
                foreach($this->_embedded->certificates as $certificate) {
                    $this->certificates[] = new Certificate($certificate);
                }
        }
    }
}