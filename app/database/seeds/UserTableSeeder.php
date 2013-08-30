<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 2/8/2556
 * Time: 13:13 น.
 * To change this template use File | Settings | File Templates.
 */

class UserTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('users')->delete();

        User::create(array(
            'type'=> 'admin',
            'email'=> 'admin@dz-service.com',
            'password'=> Hash::make('123456'),
        ));
    }
}