<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 4/9/2556
 * Time: 16:01 น.
 * To change this template use File | Settings | File Templates.
 */

class ClassTableSeeder extends Seeder {
    public function run()
    {
        DB::table('classes')->delete();

        Classes::create(array(
            'id'=> 1,
            'name'=> 'Hip Hop',
            'description'=> 'hip hop!! hip hop!! hip hop!!'
        ));
        Classes::create(array(
            'id'=> 2,
            'name'=> 'Ballet',
            'description'=> 'ballet!! ballet!! ballet!!'
        ));
        Classes::create(array(
            'id'=> 3,
            'name'=> 'Jazz',
            'description'=> 'jazz!! jazz!! jazz!!'
        ));
    }
}