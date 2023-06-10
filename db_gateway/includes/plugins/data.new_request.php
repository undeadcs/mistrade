<?php
    /**
     * Новая заявка
     */
    class CNewRequest extends CRequest {
        const   STATE_NEW = 0,
                STATE_OLD = 1;
                
        public  $state = 0;
		
    } // class CNewRequest
    