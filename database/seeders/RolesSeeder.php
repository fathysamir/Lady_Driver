<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [

            'admins.view', 'admins.create', 'admins.edit', 'admins.delete',
            'clients.view', 'clients.edit', 'clients.delete',
            'drivers.standard.car.view', 'drivers.standard.car.edit', 'drivers.standard.car.delete',
            'drivers.comfort.car.view', 'drivers.comfort.car.edit', 'drivers.comfort.car.delete',
            'drivers.scooter.view', 'drivers.scooter.edit', 'drivers.scooter.delete',
            'cities.view', 'cities.create', 'cities.edit', 'cities.delete',
            'FAQs.view', 'FAQs.create', 'FAQs.edit', 'FAQs.delete',
            'cars.marks.models.view', 'cars.marks.models.create', 'cars.marks.models.edit', 'cars.marks.models.delete',
            'scooters.marks.models.view', 'scooters.marks.models.create', 'scooters.marks.models.edit', 'scooters.marks.models.delete',
            'trips.standard.view', 'trips.comfort.view', 'trips.scooter.view',
            'settings.view', 'settings.edit',
            'cancellation.reasons.view', 'cancellation.reasons.create', 'cancellation.reasons.edit', 'cancellation.reasons.delete',
            'careers.view', 'careers.edit', 'careers.delete',
            'terms.conditions.edit', 'privacy.policy.edit', 'feedbacks.view', 'about.us.edit', 'contact.us.view', 'contact.us.reply',
            'dashboard.messages.send',
        ];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
        //create role "Employee" for users
        $roles = [
            'Client',
            'Driver',
            'Super Admin',
            'AdminAdmin111',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
        $super_admin_role = Role::where('name', 'Super Admin')->first();
        $admin_role       = Role::where('name', 'AdminAdmin111')->first();
        $permissions      = Permission::all();

        $super_admin_role->syncPermissions($permissions);
        // $user1 = User::create([
        //     'name'      => 'Admin1',
        //     'email'     => 'super.admin@ladydriver.app',
        //     'status'    => 'confirmed',
        //     'password'  => Hash::make('123456789'),
        //     'password2' => Hash::make('123456789'),
        //     'theme'     => 'theme1',
        //     'gendor'    => 'other',
        //     'mode'      => 'admin',
        //     'role'      => 'Super Admin',
        // ]);
        // $user2 = User::create([
        //     'name'      => 'Admin',
        //     'email'     => 'admin@ladydriver.app',
        //     'status'    => 'confirmed',
        //     'password'  => Hash::make('123456789'),
        //     'password2' => Hash::make('123456789'),
        //     'theme'     => 'theme1',
        //     'gendor'    => 'other',
        //     'mode'      => 'admin',
        //     'role'      => 'Admin',

        // ]);
        // $user1->syncPermissions($permissions);
        // $user1->assignRole([$admin_role->id]);
        // $user2->assignRole([$admin_role->id]);

    }
}
