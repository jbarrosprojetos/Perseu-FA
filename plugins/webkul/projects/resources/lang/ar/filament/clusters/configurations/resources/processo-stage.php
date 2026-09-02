<?php

return [
    'model-label' => 'مرحلة العملية',

    'plural-model-label' => 'مراحل العملية',

    'navigation' => [
        'title' => 'مراحل العملية',
    ],

    'form' => [
        'name' => 'الاسم',
    ],

    'table' => [
        'columns' => [
            'name'       => 'الاسم',
            'created-at' => 'تاريخ الإنشاء',
            'updated-at' => 'تاريخ التحديث',
        ],

        'groups' => [
            'name'         => 'الاسم',
            'is-completed' => 'مكتمل',
            'processo'     => 'العملية',
            'created-at'   => 'تاريخ الإنشاء',
        ],

        'filters' => [
            'is-completed' => 'مكتمل',
            'processo'     => 'العملية',
            'creator'      => 'المُنشئ',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'تم تحديث مرحلة العملية',
                    'body'  => 'تم تحديث مرحلة العملية بنجاح.',
                ],
            ],

            'restore' => [
                'notification' => [
                    'title' => 'تم استعادة مرحلة العملية',
                    'body'  => 'تم استعادة مرحلة العملية بنجاح.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'تم حذف مرحلة العملية',
                    'body'  => 'تم حذف مرحلة العملية بنجاح.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'success' => [
                        'title' => 'تم حذف مرحلة العملية نهائياً',
                        'body'  => 'تم حذف مرحلة العملية نهائياً بنجاح.',
                    ],
                    'error' => [
                        'title' => 'تعذر حذف مرحلة العملية',
                        'body'  => 'لا يمكن حذف مرحلة العملية لأنها قيد الاستخدام حالياً.',
                    ],
                ],
            ],
        ],

        'bulk-actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'تم استعادة مراحل العملية',
                    'body'  => 'تم استعادة مراحل العملية بنجاح.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'تم حذف مراحل العملية',
                    'body'  => 'تم حذف مراحل العملية بنجاح.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'تم حذف مراحل العملية نهائياً',
                    'body'  => 'تم حذف مراحل العملية نهائياً بنجاح.',
                ],
            ],
        ],
    ],
];
