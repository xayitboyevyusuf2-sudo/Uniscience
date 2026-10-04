<?php
return [
 'yearly_limit' => 6,
 'admin_email' => env('ADMIN_EMAIL'),
 'admin_password' => env('ADMIN_PASSWORD'),
 // Fields found in the OAK list (multi-field journals are stored as 'A; B'). Related pairs are an ASSUMPTION: confirm with your faculty.
 'fields' => ['Arxitektura','Biologiya','Falsafa','Farmatsevtika','Filologiya','Fizika-matematika','Geografiya','Geologiya','Harbiy','Iqtisodiyot','Islomshunoslik','Kimyo','Pedagogika','Psixologiya','Qishloq xo‘jaligi','San’atshunoslik','Siyosiy','Sotsiologiya','Tarix','Texnika','Tibbiyot','Veterinariya','Yuridik'],
 'related' => ['Iqtisodiyot'=>['Yuridik','Sotsiologiya','Siyosiy'],'Yuridik'=>['Iqtisodiyot','Siyosiy'],'Sotsiologiya'=>['Iqtisodiyot','Tarix'],'Siyosiy'=>['Iqtisodiyot','Yuridik'],'Pedagogika'=>['Psixologiya','Filologiya'],'Psixologiya'=>['Pedagogika'],'Texnika'=>['Fizika-matematika'],'Fizika-matematika'=>['Texnika'],'Biologiya'=>['Kimyo','Tibbiyot'],'Kimyo'=>['Biologiya'],'Tibbiyot'=>['Biologiya','Farmatsevtika'],'Farmatsevtika'=>['Tibbiyot'],'Tarix'=>['Filologiya','Sotsiologiya'],'Filologiya'=>['Tarix','Pedagogika']],
 'tiers' => ['A'=>10,'B'=>7,'C'=>5,'D'=>3,'E'=>1,'X'=>0],
 'positions' => ['yolgiz'=>1.0,'birinchi'=>0.8,'oxirgi'=>0.6,'ortadagi'=>0.4],
 'position_labels' => ['yolgiz'=>'Yolg‘iz','birinchi'=>'Birinchi','oxirgi'=>'Oxirgi (rahbar)','ortadagi'=>'O‘rtadagi'],
];
