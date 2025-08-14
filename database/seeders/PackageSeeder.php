<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::all()->keyBy('name');

        $packages = [
            // Science packages (6)
            ['category' => 'Science', 'name' => 'Physics Facts', 'description' => 'Fascinating facts about physics and the laws of nature', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/atom.svg', 'access_level' => 'free'],
            ['category' => 'Science', 'name' => 'Chemistry Wonders', 'description' => 'Amazing chemical reactions and discoveries', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/flask.svg', 'access_level' => 'premium'],
            ['category' => 'Science', 'name' => 'Biology Basics', 'description' => 'Essential facts about life and living organisms', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/dna.svg', 'access_level' => 'loggedIn'],
            ['category' => 'Science', 'name' => 'Earth Sciences', 'description' => 'Geology, meteorology, and Earth phenomena', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/planet.svg', 'access_level' => 'free'],
            ['category' => 'Science', 'name' => 'Quantum Physics', 'description' => 'Mind-bending quantum mechanics facts', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/atom-2.svg', 'access_level' => 'premium'],
            ['category' => 'Science', 'name' => 'Environmental Science', 'description' => 'Climate, ecology, and environmental facts', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/leaf.svg', 'access_level' => 'loggedIn'],

            // History packages (5)
            ['category' => 'History', 'name' => 'Ancient Civilizations', 'description' => 'Discover the secrets of ancient cultures', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/pyramid.svg', 'access_level' => 'free'],
            ['category' => 'History', 'name' => 'World Wars', 'description' => 'Important facts about the great wars', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/military-rank.svg', 'access_level' => 'premium'],
            ['category' => 'History', 'name' => 'Medieval Times', 'description' => 'Life in the Middle Ages', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/shield.svg', 'access_level' => 'loggedIn'],
            ['category' => 'History', 'name' => 'Renaissance Era', 'description' => 'The rebirth of art and science', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/palette.svg', 'access_level' => 'free'],
            ['category' => 'History', 'name' => 'Industrial Revolution', 'description' => 'The age of machines and innovation', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/machine.svg', 'access_level' => 'premium'],

            // Technology packages (4)
            ['category' => 'Technology', 'name' => 'Computer Science', 'description' => 'The evolution of computing and programming', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/device-desktop.svg', 'access_level' => 'free'],
            ['category' => 'Technology', 'name' => 'AI & Machine Learning', 'description' => 'Facts about artificial intelligence', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/robot.svg', 'access_level' => 'premium'],
            ['category' => 'Technology', 'name' => 'Internet & Web', 'description' => 'The history and evolution of the internet', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/world-www.svg', 'access_level' => 'loggedIn'],
            ['category' => 'Technology', 'name' => 'Mobile Technology', 'description' => 'Smartphones and mobile innovations', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/device-mobile.svg', 'access_level' => 'free'],

            // Nature packages (3)
            ['category' => 'Nature', 'name' => 'Ocean Mysteries', 'description' => 'Deep sea facts and marine life', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/wave-square.svg', 'access_level' => 'free'],
            ['category' => 'Nature', 'name' => 'Mountain Facts', 'description' => 'Amazing facts about the world\'s highest peaks', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/mountain.svg', 'access_level' => 'loggedIn'],
            ['category' => 'Nature', 'name' => 'Forest Ecosystems', 'description' => 'The wonders of forest life', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/tree.svg', 'access_level' => 'premium'],

            // Space packages (3)
            ['category' => 'Space', 'name' => 'Solar System', 'description' => 'Facts about our planetary neighborhood', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/sun.svg', 'access_level' => 'free'],
            ['category' => 'Space', 'name' => 'Galaxy Wonders', 'description' => 'Explore the mysteries of distant galaxies', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/stars.svg', 'access_level' => 'premium'],
            ['category' => 'Space', 'name' => 'Space Exploration', 'description' => 'Human missions to space', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/rocket.svg', 'access_level' => 'loggedIn'],

            // Animals packages (3)
            ['category' => 'Animals', 'name' => 'Wild Animals', 'description' => 'Amazing facts about wildlife around the world', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/deer.svg', 'access_level' => 'free'],
            ['category' => 'Animals', 'name' => 'Marine Life', 'description' => 'Incredible facts about sea creatures', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/fish.svg', 'access_level' => 'loggedIn'],
            ['category' => 'Animals', 'name' => 'Endangered Species', 'description' => 'Learn about animals at risk', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/heart-broken.svg', 'access_level' => 'premium'],

            // Geography packages (3)
            ['category' => 'Geography', 'name' => 'World Capitals', 'description' => 'Capital cities and their stories', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/map-pin.svg', 'access_level' => 'free'],
            ['category' => 'Geography', 'name' => 'Natural Wonders', 'description' => 'Earth\'s most spectacular places', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/eye.svg', 'access_level' => 'loggedIn'],
            ['category' => 'Geography', 'name' => 'Climate Zones', 'description' => 'Understanding world climate patterns', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/sun-wind.svg', 'access_level' => 'premium'],

            // Sports packages (3)
            ['category' => 'Sports', 'name' => 'Olympic Games', 'description' => 'Olympic history and records', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/medal.svg', 'access_level' => 'free'],
            ['category' => 'Sports', 'name' => 'Football Facts', 'description' => 'Amazing football statistics and history', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/ball-football.svg', 'access_level' => 'loggedIn'],
            ['category' => 'Sports', 'name' => 'Extreme Sports', 'description' => 'Adrenaline-pumping sports facts', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/parachute.svg', 'access_level' => 'premium'],

            // Art & Culture packages (2)
            ['category' => 'Art & Culture', 'name' => 'Famous Paintings', 'description' => 'Masterpieces and their stories', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/brush.svg', 'access_level' => 'free'],
            ['category' => 'Art & Culture', 'name' => 'World Cultures', 'description' => 'Traditions from around the globe', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/world.svg', 'access_level' => 'loggedIn'],

            // Food & Cooking packages (2)
            ['category' => 'Food & Cooking', 'name' => 'World Cuisines', 'description' => 'Flavors from every continent', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/tools-kitchen.svg', 'access_level' => 'free'],
            ['category' => 'Food & Cooking', 'name' => 'Cooking Science', 'description' => 'The chemistry behind cooking', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/flask.svg', 'access_level' => 'premium'],

            // Music packages (2)
            ['category' => 'Music', 'name' => 'Classical Music', 'description' => 'Great composers and their works', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/piano.svg', 'access_level' => 'free'],
            ['category' => 'Music', 'name' => 'Music History', 'description' => 'Evolution of musical genres', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/vinyl.svg', 'access_level' => 'loggedIn'],

            // Literature packages (2)
            ['category' => 'Literature', 'name' => 'Classic Literature', 'description' => 'Timeless books and authors', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/book-2.svg', 'access_level' => 'free'],
            ['category' => 'Literature', 'name' => 'Poetry Facts', 'description' => 'Beautiful verses and their meanings', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/writing.svg', 'access_level' => 'premium'],

            // Health & Medicine packages (2)
            ['category' => 'Health & Medicine', 'name' => 'Human Body', 'description' => 'Amazing facts about our anatomy', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/heart.svg', 'access_level' => 'free'],
            ['category' => 'Health & Medicine', 'name' => 'Medical Breakthroughs', 'description' => 'Revolutionary medical discoveries', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/stethoscope.svg', 'access_level' => 'loggedIn'],

            // Psychology packages (2)
            ['category' => 'Psychology', 'name' => 'Human Behavior', 'description' => 'Why we act the way we do', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/users.svg', 'access_level' => 'free'],
            ['category' => 'Psychology', 'name' => 'Memory & Learning', 'description' => 'How our brains store information', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/bulb.svg', 'access_level' => 'premium'],

            // Economics packages (2)
            ['category' => 'Economics', 'name' => 'World Economy', 'description' => 'Global economic systems and trends', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/chart-pie.svg', 'access_level' => 'free'],
            ['category' => 'Economics', 'name' => 'Cryptocurrency', 'description' => 'Digital currencies and blockchain', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/coin.svg', 'access_level' => 'loggedIn'],

            // Movies & Cinema packages (2)
            ['category' => 'Movies & Cinema', 'name' => 'Film History', 'description' => 'Evolution of cinema and filmmaking', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/camera.svg', 'access_level' => 'free'],
            ['category' => 'Movies & Cinema', 'name' => 'Movie Trivia', 'description' => 'Behind-the-scenes facts', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/theater.svg', 'access_level' => 'premium'],

            // Architecture packages (2)
            ['category' => 'Architecture', 'name' => 'Famous Buildings', 'description' => 'Iconic structures around the world', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/building-bridge.svg', 'access_level' => 'free'],
            ['category' => 'Architecture', 'name' => 'Architectural Styles', 'description' => 'Different design movements', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/ruler.svg', 'access_level' => 'loggedIn'],

            // Transportation packages (2)
            ['category' => 'Transportation', 'name' => 'Aviation History', 'description' => 'The story of human flight', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/plane.svg', 'access_level' => 'free'],
            ['category' => 'Transportation', 'name' => 'Future Transport', 'description' => 'Tomorrow\'s transportation technologies', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/car-electric.svg', 'access_level' => 'premium'],

            // Language packages (2)
            ['category' => 'Language', 'name' => 'World Languages', 'description' => 'Fascinating facts about languages', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/language.svg', 'access_level' => 'free'],
            ['category' => 'Language', 'name' => 'Etymology', 'description' => 'Origins and evolution of words', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/letter-case.svg', 'access_level' => 'loggedIn'],

            // Environment packages (2)
            ['category' => 'Environment', 'name' => 'Climate Change', 'description' => 'Understanding our changing climate', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/temperature.svg', 'access_level' => 'free'],
            ['category' => 'Environment', 'name' => 'Renewable Energy', 'description' => 'Clean energy sources and technology', 'icon_url' => 'https://cdn.jsdelivr.net/npm/tabler-icons@latest/icons/windmill.svg', 'access_level' => 'premium'],
        ];

        foreach ($packages as $packageData) {
            Package::create([
                'category_id' => $categories[$packageData['category']]->id,
                'name' => $packageData['name'],
                'description' => $packageData['description'],
                'icon_url' => $packageData['icon_url'],
                'access_level' => $packageData['access_level'],
            ]);
        }
    }
}
