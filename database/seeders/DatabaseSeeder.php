<?php

namespace Database\Seeders;

use App\Models\{Equipment, EquipmentItem, Facility, FermsRequest, Notice, Reservation, Transaction, User};
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Kenneth Sigua', 'email' => 'student@mcc.edu.ph', 'role' => 'student', 'organization_class' => 'BSIT 3B'],
            ['name' => 'Student Council', 'email' => 'organization@mcc.edu.ph', 'role' => 'organization', 'organization_class' => 'Student Council'],
            ['name' => 'Maria Dela Pena', 'email' => 'staff@mcc.edu.ph', 'role' => 'staff'],
            ['name' => 'Ariel Amoguis', 'email' => 'admin@mcc.edu.ph', 'role' => 'admin'],
            ['name' => 'Super Administrator', 'email' => 'superadmin@mcc.edu.ph', 'role' => 'superadmin'],
        ];

        foreach ($users as $user) {
            User::firstOrCreate(
                ['email' => $user['email']],
                $user + ['is_active' => true],
            );
        }

        $facilities = [
            ['Gymnasium - Court A', 'Sports Complex', 'Courts', 150],
            ['Gymnasium - Court B', 'Sports Complex', 'Courts', 150],
            ['AVR Room', 'Main Building', 'Rooms', 40],
            ['Conference Room 1', 'Admin Building', 'Rooms', 16],
            ['Conference Room 2', 'Admin Building', 'Rooms', 24],
            ['Cultural Hall', 'MCC Main', 'Halls', 300],
            ['Open Field', 'East Grounds', 'Outdoor', 300],
            ['Computer Laboratory 1', 'Technology Building', 'Laboratories', 40],
            ['Computer Laboratory 2', 'Technology Building', 'Laboratories', 35],
            ['Library Discussion Room', 'Library Building', 'Rooms', 12],
            ['Covered Court', 'Sports Complex', 'Courts', 200],
            ['Student Activity Center', 'Student Center', 'Rooms', 80],
        ];

        foreach ($facilities as [$name, $location, $category, $capacity]) {
            Facility::firstOrCreate(
                ['name' => $name],
                ['location' => $location, 'category' => $category, 'capacity' => $capacity],
            );
        }

        $equipment = [
            ['Projector', 'AV', 6],
            ['Wireless Microphone', 'AV', 8],
            ['Portable Speaker', 'AV', 6],
            ['HDMI Cable', 'AV', 12],
            ['Extension Cord', 'Electrical', 16],
            ['Laptop', 'IT', 10],
            ['Tablet', 'IT', 12],
            ['Webcam', 'IT', 8],
            ['Badminton Set', 'Sports', 12],
            ['Volleyball', 'Sports', 10],
            ['Basketball', 'Sports', 10],
            ['Folding Tables', 'Furniture', 20],
            ['Folding Chairs', 'Furniture', 80],
            ['Whiteboard', 'Furniture', 8],
            ['Portable Podium', 'Furniture', 3],
        ];

        foreach ($equipment as [$name, $category, $quantity]) {
            Equipment::firstOrCreate(
                ['name' => $name],
                ['category' => $category, 'total_quantity' => $quantity, 'available_quantity' => $quantity],
            );
        }

        $this->seedDemoActivity();
    }

    private function seedDemoActivity(): void
    {
        $facility = Facility::where('name', 'AVR Room')->firstOrFail();
        $secondFacility = Facility::where('name', 'Gymnasium - Court A')->firstOrFail();
        $projector = Equipment::where('name', 'Projector')->firstOrFail();
        $microphone = Equipment::where('name', 'Wireless Microphone')->firstOrFail();
        $speaker = Equipment::where('name', 'Portable Speaker')->firstOrFail();
        foreach ([
            ['student@mcc.edu.ph', 'BSIT 3B'],
            ['organization@mcc.edu.ph', 'Student Council'],
        ] as [$email, $organizationClass]) {
            $user = User::where('email', $email)->firstOrFail();
            $approvedStartsAt = null;
            $approvedEndsAt = null;
            for ($days = 30; $days <= 395; $days++) {
                $candidateStart = now()->addDays($days)->setTime(9, 0);
                $candidateEnd = (clone $candidateStart)->addHours(2);
                if (!Reservation::conflicting($facility->id, $candidateStart, $candidateEnd)->exists()) {
                    $approvedStartsAt = $candidateStart;
                    $approvedEndsAt = $candidateEnd;
                    break;
                }
            }
            if (!$approvedStartsAt || !$approvedEndsAt) {
                throw new \RuntimeException('Unable to find an available date for a sample facility reservation.');
            }

            foreach ([
                ['title' => 'Request update', 'message' => 'Your facility reservation request has been approved.', 'type' => 'success'],
                ['title' => 'Equipment return reminder', 'message' => 'Please return the borrowed equipment by the agreed due date.', 'type' => 'warning'],
                ['title' => 'Request needs attention', 'message' => 'One of your sample equipment requests was not approved.', 'type' => 'danger'],
                ['title' => 'System announcement', 'message' => 'Welcome to the equipment and facility reservation portal.', 'type' => 'info'],
            ] as $notice) {
                Notice::firstOrCreate(
                    ['user_id' => $user->id, 'title' => $notice['title']],
                    ['message' => $notice['message'], 'type' => $notice['type']],
                );
            }

            $approvedFacility = $this->demoRequest($user, $organizationClass, 'facility', 'Sample facility reservation - approved', 'approved');
            Reservation::firstOrCreate(
                ['request_id' => $approvedFacility->id],
                ['facility_id' => $facility->id, 'starts_at' => $approvedStartsAt, 'ends_at' => $approvedEndsAt, 'status' => 'approved'],
            );

            $cancelledFacility = $this->demoRequest($user, $organizationClass, 'facility', 'Sample facility reservation - cancelled', 'cancelled');
            Reservation::firstOrCreate(
                ['request_id' => $cancelledFacility->id],
                ['facility_id' => $secondFacility->id, 'starts_at' => now()->subDays(10)->setTime(13, 0), 'ends_at' => now()->subDays(10)->setTime(14, 0), 'status' => 'cancelled'],
            );

            $rejectedEquipment = $this->demoRequest($user, $organizationClass, 'equipment', 'Sample equipment request - rejected', 'rejected');
            EquipmentItem::firstOrCreate(
                ['request_id' => $rejectedEquipment->id],
                ['equipment_id' => $speaker->id, 'quantity' => 1, 'borrow_date' => today()->subDays(5), 'expected_return' => today()->subDays(4)],
            );

            $activeEquipment = $this->demoRequest($user, $organizationClass, 'equipment', 'Sample equipment loan - released', 'released');
            EquipmentItem::firstOrCreate(
                ['request_id' => $activeEquipment->id],
                ['equipment_id' => $projector->id, 'quantity' => 1, 'borrow_date' => today()->subDay(), 'expected_return' => today()->addDays(2)],
            );
            $activeTransaction = Transaction::firstOrCreate(
                ['request_id' => $activeEquipment->id],
                [
                    'equipment_id' => $projector->id,
                    'user_id' => $user->id,
                    'quantity' => 1,
                    'borrow_date' => today()->subDay(),
                    'expected_return' => today()->addDays(2),
                    'status' => 'borrowed',
                ],
            );
            if ($activeTransaction->wasRecentlyCreated) {
                $projector->decrement('available_quantity');
            }

            $returnedEquipment = $this->demoRequest($user, $organizationClass, 'equipment', 'Sample equipment loan - returned', 'returned');
            EquipmentItem::firstOrCreate(
                ['request_id' => $returnedEquipment->id],
                ['equipment_id' => $microphone->id, 'quantity' => 1, 'borrow_date' => today()->subDays(12), 'expected_return' => today()->subDays(11)],
            );
            Transaction::firstOrCreate(
                ['request_id' => $returnedEquipment->id],
                [
                    'equipment_id' => $microphone->id,
                    'user_id' => $user->id,
                    'quantity' => 1,
                    'borrow_date' => today()->subDays(12),
                    'expected_return' => today()->subDays(11),
                    'actual_return' => today()->subDays(11)->setTime(15, 30),
                    'status' => 'returned',
                ],
            );
        }
    }

    private function demoRequest(User $user, string $organizationClass, string $type, string $purpose, string $status): FermsRequest
    {
        return FermsRequest::firstOrCreate(
            ['user_id' => $user->id, 'purpose' => $purpose],
            ['type' => $type, 'organization_class' => $organizationClass, 'status' => $status],
        );
    }
}
