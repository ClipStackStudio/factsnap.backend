<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\Fact;
use Illuminate\Database\Seeder;

class FactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = Package::all()->keyBy('name');

        $factsData = [
            'Physics Facts' => [
                ['fact' => 'Light travels at exactly 299,792,458 meters per second in a vacuum.', 'author' => 'Albert Einstein'],
                ['fact' => 'A photon has zero mass but carries energy and momentum.', 'author' => 'Max Planck'],
                ['fact' => 'Time moves slower in stronger gravitational fields.', 'author' => 'Albert Einstein'],
                ['fact' => 'Quantum entanglement allows particles to affect each other instantly across any distance.', 'author' => 'Erwin Schrödinger'],
                ['fact' => 'The universe is expanding at an accelerating rate.', 'author' => 'Edwin Hubble'],
                ['fact' => 'Sound travels about 4 times faster in water than in air.', 'author' => null],
                ['fact' => 'Lightning is 5 times hotter than the surface of the Sun.', 'author' => null],
                ['fact' => 'Neutron stars are so dense that a teaspoon would weigh 6 billion tons.', 'author' => null],
                ['fact' => 'The magnetic field of a magnetar is a trillion times stronger than Earth\'s.', 'author' => null],
                ['fact' => 'Absolute zero is -273.15°C, where all molecular motion stops.', 'author' => null],
            ],

            'Chemistry Wonders' => [
                ['fact' => 'Water is the only substance that naturally exists in all three states on Earth.', 'author' => null],
                ['fact' => 'Diamond and graphite are both pure carbon but have completely different properties.', 'author' => null],
                ['fact' => 'The periodic table has 118 confirmed elements.', 'author' => 'Dmitri Mendeleev'],
                ['fact' => 'Gold is so unreactive that it can last millions of years without tarnishing.', 'author' => null],
                ['fact' => 'Helium is the only element that was discovered in space before being found on Earth.', 'author' => null],
                ['fact' => 'A single drop of water contains more than 1.5 billion billion billion molecules.', 'author' => null],
                ['fact' => 'Glass is actually a liquid that moves extremely slowly.', 'author' => null],
                ['fact' => 'Hydrogen is the most abundant element in the universe.', 'author' => null],
            ],

            'Biology Basics' => [
                ['fact' => 'Humans share about 60% of their DNA with bananas.', 'author' => null],
                ['fact' => 'The human brain contains approximately 86 billion neurons.', 'author' => null],
                ['fact' => 'Your body produces about 25 million new cells every second.', 'author' => null],
                ['fact' => 'Octopuses have three hearts and blue blood.', 'author' => null],
                ['fact' => 'A single human cell contains about 6 billion base pairs of DNA.', 'author' => null],
                ['fact' => 'Bacteria make up about 90% of the cells in your body.', 'author' => null],
                ['fact' => 'The largest living organism is a fungus in Oregon covering 2,385 acres.', 'author' => null],
                ['fact' => 'Tardigrades can survive in the vacuum of space.', 'author' => null],
            ],

            'Earth Sciences' => [
                ['fact' => 'The Earth\'s core is as hot as the surface of the Sun.', 'author' => null],
                ['fact' => 'A single lightning bolt contains enough energy to power a home for weeks.', 'author' => null],
                ['fact' => 'The Sahara Desert was once a lush, green landscape.', 'author' => null],
                ['fact' => 'Diamonds can form in space and rain down on planets.', 'author' => null],
                ['fact' => 'The Earth\'s magnetic field protects us from deadly solar radiation.', 'author' => null],
                ['fact' => 'Earthquakes can make days shorter by redistributing Earth\'s mass.', 'author' => null],
            ],

            'Ancient Civilizations' => [
                ['fact' => 'The Great Wall of China took over 2,000 years to build.', 'author' => null],
                ['fact' => 'The ancient Egyptians invented the 365-day calendar.', 'author' => null],
                ['fact' => 'The Library of Alexandria was the largest library in the ancient world.', 'author' => null],
                ['fact' => 'Romans invented concrete that could set underwater.', 'author' => null],
                ['fact' => 'The Mayan calendar was more accurate than the one we use today.', 'author' => null],
                ['fact' => 'Ancient Greeks invented the steam engine 2,000 years before the Industrial Revolution.', 'author' => null],
                ['fact' => 'The Antikythera mechanism was an ancient Greek analog computer.', 'author' => null],
                ['fact' => 'Stonehenge is older than the Pyramids of Giza.', 'author' => null],
            ],

            'Computer Science' => [
                ['fact' => 'The first computer bug was an actual bug - a moth trapped in a relay.', 'author' => 'Grace Hopper'],
                ['fact' => 'The first computer weighed more than 27 tons.', 'author' => null],
                ['fact' => 'There are more possible games of chess than atoms in the observable universe.', 'author' => null],
                ['fact' => 'The @ symbol was used in email for the first time in 1971.', 'author' => 'Ray Tomlinson'],
                ['fact' => 'The term "debugging" comes from removing actual bugs from computer hardware.', 'author' => 'Grace Hopper'],
                ['fact' => 'The first 1GB hard drive cost $40,000 in 1980.', 'author' => null],
                ['fact' => 'CAPTCHA stands for "Completely Automated Public Turing test to tell Computers and Humans Apart".', 'author' => null],
                ['fact' => 'The first programming language was called Plankalkül, created in 1948.', 'author' => 'Konrad Zuse'],
            ],

            'Ocean Mysteries' => [
                ['fact' => 'We have explored less than 5% of our oceans.', 'author' => null],
                ['fact' => 'The ocean contains 99% of the living space on Earth.', 'author' => null],
                ['fact' => 'The Mariana Trench is deeper than Mount Everest is tall.', 'author' => null],
                ['fact' => 'Ocean waves can travel thousands of miles without losing energy.', 'author' => null],
                ['fact' => 'The Great Barrier Reef can be seen from space.', 'author' => null],
                ['fact' => 'Coral reefs support 25% of all marine species despite covering less than 1% of the ocean.', 'author' => null],
                ['fact' => 'The deepest part of the ocean is about 7 miles down.', 'author' => null],
                ['fact' => 'More people have been to space than to the deepest part of the ocean.', 'author' => null],
            ],

            'Solar System' => [
                ['fact' => 'Jupiter is so large that all other planets could fit inside it.', 'author' => null],
                ['fact' => 'A day on Venus is longer than its year.', 'author' => null],
                ['fact' => 'Saturn would float in water because it\'s less dense.', 'author' => null],
                ['fact' => 'The Sun contains 99.86% of the mass in our solar system.', 'author' => null],
                ['fact' => 'Mars has the largest volcano in the solar system.', 'author' => null],
                ['fact' => 'Neptune has winds that can reach 1,200 mph.', 'author' => null],
                ['fact' => 'Mercury has no atmosphere and temperature swings of 1,100°F.', 'author' => null],
                ['fact' => 'Uranus rotates on its side, likely due to an ancient collision.', 'author' => null],
            ],

            'Wild Animals' => [
                ['fact' => 'Elephants can hear sounds from up to 6 miles away.', 'author' => null],
                ['fact' => 'A group of flamingos is called a flamboyance.', 'author' => null],
                ['fact' => 'Koalas sleep up to 22 hours per day.', 'author' => null],
                ['fact' => 'Tigers have striped skin, not just striped fur.', 'author' => null],
                ['fact' => 'Giraffes only need 30 minutes to 2 hours of sleep per day.', 'author' => null],
                ['fact' => 'A shrimp\'s heart is in its head.', 'author' => null],
                ['fact' => 'Penguins can jump 6 feet in the air.', 'author' => null],
                ['fact' => 'Polar bears have black skin under their white fur.', 'author' => null],
            ],

            'World Capitals' => [
                ['fact' => 'La Paz, Bolivia is the highest capital city in the world at 12,000 feet.', 'author' => null],
                ['fact' => 'Naypyidaw, Myanmar was built specifically to be the capital in 2006.', 'author' => null],
                ['fact' => 'Vatican City is the smallest capital in the world.', 'author' => null],
                ['fact' => 'Canberra was chosen as Australia\'s capital as a compromise between Sydney and Melbourne.', 'author' => null],
                ['fact' => 'Reykjavik, Iceland is the northernmost capital in the world.', 'author' => null],
                ['fact' => 'Brasília, Brazil was built from scratch in just 41 months.', 'author' => null],
            ],

            'Olympic Games' => [
                ['fact' => 'The Olympic flame has never been extinguished since 1928.', 'author' => null],
                ['fact' => 'The five Olympic rings represent the five inhabited continents.', 'author' => null],
                ['fact' => 'Gold medals are actually made of silver and covered with gold.', 'author' => null],
                ['fact' => 'The ancient Olympics lasted for nearly 12 centuries.', 'author' => null],
                ['fact' => 'Women weren\'t allowed to compete in the Olympics until 1900.', 'author' => null],
                ['fact' => 'The Tokyo 2020 Olympics medals were made from recycled electronics.', 'author' => null],
            ],

            'Famous Paintings' => [
                ['fact' => 'The Mona Lisa has no eyebrows because it was fashionable to shave them off.', 'author' => null],
                ['fact' => 'Van Gogh only sold one painting during his lifetime.', 'author' => null],
                ['fact' => 'The Scream by Edvard Munch was inspired by a blood-red sunset.', 'author' => null],
                ['fact' => 'Guernica by Picasso is painted only in black, white, and gray.', 'author' => null],
                ['fact' => 'The Last Supper took Leonardo da Vinci 4 years to complete.', 'author' => null],
            ],

            'World Cuisines' => [
                ['fact' => 'Chocolate was once used as currency by the Aztecs.', 'author' => null],
                ['fact' => 'Honey never spoils - archaeologists have found edible honey in Egyptian tombs.', 'author' => null],
                ['fact' => 'Vanilla is the second most expensive spice after saffron.', 'author' => null],
                ['fact' => 'Pizza Margherita was named after Queen Margherita of Italy.', 'author' => null],
                ['fact' => 'Fortune cookies were actually invented in San Francisco, not China.', 'author' => null],
            ],

            'Classical Music' => [
                ['fact' => 'Mozart wrote his first symphony when he was just 8 years old.', 'author' => null],
                ['fact' => 'Beethoven continued composing even after he became completely deaf.', 'author' => null],
                ['fact' => 'Bach had 20 children and many became musicians.', 'author' => null],
                ['fact' => 'Chopin\'s heart is preserved in a church in Warsaw.', 'author' => null],
                ['fact' => 'Handel\'s Messiah was composed in just 24 days.', 'author' => null],
            ],

            'Human Body' => [
                ['fact' => 'Your brain uses 20% of your body\'s total energy.', 'author' => null],
                ['fact' => 'You produce about 1.5 liters of saliva every day.', 'author' => null],
                ['fact' => 'Your heart beats about 100,000 times per day.', 'author' => null],
                ['fact' => 'You blink about 17,000 times per day.', 'author' => null],
                ['fact' => 'Your stomach gets an entirely new lining every 3-5 days.', 'author' => null],
                ['fact' => 'Humans are the only animals that can blush.', 'author' => null],
            ],

            'World Economy' => [
                ['fact' => 'If Apple were a country, it would be the 8th largest economy in the world.', 'author' => null],
                ['fact' => 'The total value of all money in the world is about $80 trillion.', 'author' => null],
                ['fact' => 'Norway has the largest sovereign wealth fund worth over $1 trillion.', 'author' => null],
                ['fact' => 'The New York Stock Exchange is worth more than the next 3 largest exchanges combined.', 'author' => null],
            ],

            'Film History' => [
                ['fact' => 'The first film ever made was only 2.11 seconds long.', 'author' => null],
                ['fact' => 'Charlie Chaplin once entered a Charlie Chaplin look-alike contest and came in third.', 'author' => null],
                ['fact' => 'The Wilhelm Scream has been used in over 400 films.', 'author' => null],
                ['fact' => 'Citizen Kane was inspired by newspaper magnate William Randolph Hearst.', 'author' => null],
            ],

            'Famous Buildings' => [
                ['fact' => 'The Eiffel Tower was only meant to be temporary.', 'author' => null],
                ['fact' => 'The Empire State Building was built in just 410 days.', 'author' => null],
                ['fact' => 'The Leaning Tower of Pisa took 344 years to complete.', 'author' => null],
                ['fact' => 'The Burj Khalifa is so tall you can see two sunsets from different floors.', 'author' => null],
            ],

            'Aviation History' => [
                ['fact' => 'The Wright brothers\' first flight lasted only 12 seconds.', 'author' => null],
                ['fact' => 'Amelia Earhart was the first woman to fly solo across the Atlantic.', 'author' => null],
                ['fact' => 'The Concorde could fly faster than the speed of sound.', 'author' => null],
                ['fact' => 'The Boeing 747 has more than 6 million parts.', 'author' => null],
            ],

            'World Languages' => [
                ['fact' => 'Papua New Guinea has over 800 languages, more than any other country.', 'author' => null],
                ['fact' => 'Mandarin Chinese is spoken by more people than any other language.', 'author' => null],
                ['fact' => 'The word "set" has the most different meanings in English.', 'author' => null],
                ['fact' => 'Shakespeare invented over 1,700 words that we still use today.', 'author' => null],
            ],

            'Climate Change' => [
                ['fact' => 'The last decade was the warmest on record.', 'author' => null],
                ['fact' => 'Arctic sea ice is declining at a rate of 13% per decade.', 'author' => null],
                ['fact' => 'CO2 levels are the highest they\'ve been in 3 million years.', 'author' => null],
                ['fact' => 'Rising sea levels threaten 630 million people worldwide.', 'author' => null],
            ],
        ];

        foreach ($factsData as $packageName => $facts) {
            if (!isset($packages[$packageName])) continue;
            
            foreach ($facts as $factData) {
                Fact::create([
                    'package_id' => $packages[$packageName]->id,
                    'fact' => $factData['fact'],
                    'author' => $factData['author'],
                ]);
            }
        }
    }
}
