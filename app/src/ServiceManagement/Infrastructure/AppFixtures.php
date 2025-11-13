<?php

namespace App\ServiceManagement\Infrastructure;

use App\IdentityAccess\Domain\User;
use App\Property\Domain\House;
use App\ServiceManagement\Domain\ServiceRequest;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Create Users with different roles
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setFirstName('System');
        $admin->setLastName('Administrator');
        $admin->setPhone('+1234567890');
        $admin->setIsActive(true);
        $manager->persist($admin);

        $manager1 = new User();
        $manager1->setEmail('manager@example.com');
        $manager1->setRoles(['ROLE_MANAGER']);
        $manager1->setPassword($this->passwordHasher->hashPassword($manager1, 'manager123'));
        $manager1->setFirstName('John');
        $manager1->setLastName('Manager');
        $manager1->setPhone('+1234567891');
        $manager1->setIsActive(true);
        $manager->persist($manager1);

        $cleaner1 = new User();
        $cleaner1->setEmail('cleaner1@example.com');
        $cleaner1->setRoles(['ROLE_CLEANER']);
        $cleaner1->setPassword($this->passwordHasher->hashPassword($cleaner1, 'cleaner123'));
        $cleaner1->setFirstName('Jane');
        $cleaner1->setLastName('Smith');
        $cleaner1->setPhone('+1234567892');
        $cleaner1->setIsActive(true);
        $manager->persist($cleaner1);

        $cleaner2 = new User();
        $cleaner2->setEmail('cleaner2@example.com');
        $cleaner2->setRoles(['ROLE_CLEANER']);
        $cleaner2->setPassword($this->passwordHasher->hashPassword($cleaner2, 'cleaner123'));
        $cleaner2->setFirstName('Mike');
        $cleaner2->setLastName('Johnson');
        $cleaner2->setPhone('+1234567893');
        $cleaner2->setIsActive(true);
        $manager->persist($cleaner2);

        $owner1 = new User();
        $owner1->setEmail('owner1@example.com');
        $owner1->setRoles(['ROLE_OWNER']);
        $owner1->setPassword($this->passwordHasher->hashPassword($owner1, 'owner123'));
        $owner1->setFirstName('Robert');
        $owner1->setLastName('Brown');
        $owner1->setPhone('+1234567894');
        $owner1->setIsActive(true);
        $manager->persist($owner1);

        $owner2 = new User();
        $owner2->setEmail('owner2@example.com');
        $owner2->setRoles(['ROLE_OWNER']);
        $owner2->setPassword($this->passwordHasher->hashPassword($owner2, 'owner123'));
        $owner2->setFirstName('Sarah');
        $owner2->setLastName('Williams');
        $owner2->setPhone('+1234567895');
        $owner2->setIsActive(true);
        $manager->persist($owner2);

        // Create Houses
        $house1 = new House();
        $house1->setName('Seaside Villa');
        $house1->setAddress('123 Ocean Drive');
        $house1->setCity('Miami Beach');
        $house1->setPostalCode('33139');
        $house1->setCountry('USA');
        $house1->setDescription('Beautiful beachfront villa with stunning ocean views.');
        $house1->setBedrooms(4);
        $house1->setBathrooms(3);
        $house1->setSquareMeters(250);
        $house1->setOwner($owner1);
        $house1->setIsActive(true);
        $manager->persist($house1);

        $house2 = new House();
        $house2->setName('Mountain Retreat');
        $house2->setAddress('456 Alpine Road');
        $house2->setCity('Aspen');
        $house2->setPostalCode('81611');
        $house2->setCountry('USA');
        $house2->setDescription('Cozy mountain cabin perfect for winter getaways.');
        $house2->setBedrooms(3);
        $house2->setBathrooms(2);
        $house2->setSquareMeters(180);
        $house2->setOwner($owner1);
        $house2->setIsActive(true);
        $manager->persist($house2);

        $house3 = new House();
        $house3->setName('City Loft');
        $house3->setAddress('789 Urban Street, Apt 12A');
        $house3->setCity('New York');
        $house3->setPostalCode('10001');
        $house3->setCountry('USA');
        $house3->setDescription('Modern loft in the heart of Manhattan.');
        $house3->setBedrooms(2);
        $house3->setBathrooms(2);
        $house3->setSquareMeters(120);
        $house3->setOwner($owner2);
        $house3->setIsActive(true);
        $manager->persist($house3);

        $house4 = new House();
        $house4->setName('Lakeside Cottage');
        $house4->setAddress('321 Waterfront Lane');
        $house4->setCity('Lake Tahoe');
        $house4->setPostalCode('96150');
        $house4->setCountry('USA');
        $house4->setDescription('Charming cottage with private lake access.');
        $house4->setBedrooms(3);
        $house4->setBathrooms(2);
        $house4->setSquareMeters(160);
        $house4->setOwner($owner2);
        $house4->setIsActive(true);
        $manager->persist($house4);

        // Create Service Requests
        $request1 = new ServiceRequest();
        $request1->setHouse($house1);
        $request1->setServiceType('cleaning');
        $request1->setScheduledDate(new \DateTimeImmutable('+2 days'));
        $request1->setDescription('Standard cleaning before guest arrival');
        $request1->setEstimatedDuration('3.5');
        $request1->setCreatedBy($manager1);
        $request1->setAssignedCleaner($cleaner1);
        $request1->setPriority('normal');
        $request1->setCurrentPlace('scheduled');
        $manager->persist($request1);

        $request2 = new ServiceRequest();
        $request2->setHouse($house1);
        $request2->setServiceType('deep_cleaning');
        $request2->setScheduledDate(new \DateTimeImmutable('+5 days'));
        $request2->setDescription('Deep cleaning after long-term rental');
        $request2->setEstimatedDuration('6.0');
        $request2->setCreatedBy($manager1);
        $request2->setAssignedCleaner($cleaner2);
        $request2->setPriority('high');
        $request2->setCurrentPlace('assigned');
        $manager->persist($request2);

        $request3 = new ServiceRequest();
        $request3->setHouse($house2);
        $request3->setServiceType('maintenance');
        $request3->setScheduledDate(new \DateTimeImmutable('+1 day'));
        $request3->setDescription('Check heating system before winter season');
        $request3->setEstimatedDuration('2.0');
        $request3->setCreatedBy($manager1);
        $request3->setPriority('urgent');
        $request3->setCurrentPlace('approved');
        $manager->persist($request3);

        $request4 = new ServiceRequest();
        $request4->setHouse($house3);
        $request4->setServiceType('inspection');
        $request4->setScheduledDate(new \DateTimeImmutable('+7 days'));
        $request4->setDescription('Monthly property inspection');
        $request4->setEstimatedDuration('1.5');
        $request4->setCreatedBy($manager1);
        $request4->setAssignedCleaner($cleaner1);
        $request4->setPriority('normal');
        $request4->setCurrentPlace('in_progress');
        $manager->persist($request4);

        $request5 = new ServiceRequest();
        $request5->setHouse($house4);
        $request5->setServiceType('pool_maintenance');
        $request5->setScheduledDate(new \DateTimeImmutable('+3 days'));
        $request5->setDescription('Weekly pool cleaning and chemical balance');
        $request5->setEstimatedDuration('2.5');
        $request5->setCreatedBy($manager1);
        $request5->setAssignedCleaner($cleaner2);
        $request5->setPriority('normal');
        $request5->setCurrentPlace('scheduled');
        $manager->persist($request5);

        $manager->flush();
    }
}
