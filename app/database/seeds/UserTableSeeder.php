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
            'email'=> 'admin@dancezone.com',
            'username'=> 'admin',
            'first_name'=> 'admin',
            'password'=> Hash::make('123456'),
        ));

        User::create(array(
            'type'=> 'member',
            'email'=> 'user1@example.com',
            'username'=> 'user1@example.com',
            'first_name'=> 'user1',
            'password'=> Hash::make('123456'),
        ));

        User::create(array(
            'type'=> 'normal',
            'email'=> 'user2@example.com',
            'username'=> 'user2@example.com',
            'first_name'=> 'user2',
            'password'=> Hash::make('123456'),
        ));

        User::create(array(
            'type'=> 'normal',
            'email'=> 'user3@example.com',
            'username'=> 'user3@example.com',
            'first_name'=> 'user3',
            'password'=> Hash::make('123456'),
        ));
    }
}