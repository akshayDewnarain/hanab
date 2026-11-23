<?php

namespace Database\Seeders;

use Domain\Auth\Models\User;
use Domain\HelpCenter\Models\HelpArticle;
use Domain\HelpCenter\Models\HelpCategory;
use Domain\HelpCenter\Support\HelpContent;
use Illuminate\Database\Seeder;

class HelpGuideSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $categories = [
            ['name' => 'Aan de slag', 'slug' => 'aan-de-slag', 'icon' => 'material-symbols:flag-outline-rounded', 'sort_order' => 10, 'description' => 'De eerste stappen in Hanab.'],
            ['name' => "Collega's", 'slug' => 'collegas', 'icon' => 'material-symbols:groups-outline-rounded', 'sort_order' => 20, 'description' => 'Zoeken, filteren en profielen bekijken.'],
            ['name' => 'Medewerkersbeheer', 'slug' => 'medewerkersbeheer', 'icon' => 'material-symbols:edit-outline-rounded', 'sort_order' => 30, 'description' => 'Gegevens van medewerkers bijwerken.'],
            ['name' => 'Organogram', 'slug' => 'organogram', 'icon' => 'material-symbols:account-tree-outline-rounded', 'sort_order' => 40, 'description' => 'Leiding, locatie en rollen.'],
            ['name' => 'Importeren en exporteren', 'slug' => 'importeren-en-exporteren', 'icon' => 'material-symbols:upload-file-outline-rounded', 'sort_order' => 50, 'description' => 'Bestanden inlezen en uitdraaien.'],
            ['name' => 'Vaardigheden en certificaten', 'slug' => 'vaardigheden-en-certificaten', 'icon' => 'material-symbols:workspace-premium-outline-rounded', 'sort_order' => 60, 'description' => 'Labels op profielen en in het beheer.'],
            ['name' => 'Berichten', 'slug' => 'berichten', 'icon' => 'material-symbols:forum-outline-rounded', 'sort_order' => 70, 'description' => 'Mededelingen voor collega\'s.'],
            ['name' => 'Gebruikersbeheer', 'slug' => 'gebruikersbeheer', 'icon' => 'material-symbols:manage-accounts-outline-rounded', 'sort_order' => 80, 'description' => 'Accounts en rollen.'],
            ['name' => 'Instellingen', 'slug' => 'instellingen', 'icon' => 'material-symbols:settings-outline-rounded', 'sort_order' => 90, 'description' => 'Toegang en configuratie.'],
        ];

        foreach ($categories as $category) {
            HelpCategory::query()->updateOrCreate(
                ['slug' => $category['slug']],
                [...$category, 'is_active' => true],
            );
        }

        $authorId = User::query()->orderBy('id')->value('id');
        foreach ($this->guides() as $guide) {
            $categoryId = HelpCategory::query()->where('slug', $guide['category'])->value('id');
            $prepared = HelpContent::prepare($guide['content']);
            if ($prepared === null || $categoryId === null) {
                continue;
            }
            $existing = HelpArticle::query()->where('slug', $guide['slug'])->first();
            if ($existing !== null && (int) $existing->version > 1) {
                continue;
            }
            $values = [
                'category_id' => $categoryId,
                'editor_id' => $authorId,
                'title' => $guide['title'],
                'summary' => $guide['summary'],
                'content' => $prepared['document'],
                'search_text' => $prepared['text'],
                'keywords' => $guide['keywords'],
                'status' => HelpArticle::STATUS_PUBLISHED,
                'visibility' => HelpArticle::VISIBILITY_AUTHENTICATED,
                'context_key' => $guide['context_key'],
                'audience_permission' => $guide['audience'] ?? null,
                'feature_path' => $guide['feature_path'],
                'sort_order' => $guide['sort_order'],
                'is_getting_started' => $guide['started'],
            ];
            if ($existing === null) {
                $article = HelpArticle::query()->create([
                    ...$values,
                    'slug' => $guide['slug'],
                    'author_id' => $authorId,
                    'version' => 1,
                    'published_at' => now(),
                ]);
            } else {
                $existing->fill($values)->save();
                $article = $existing;
            }
            $revision = $article->revisions()->where('number', 1)->first();
            if ($revision === null) {
                $article->revisions()->create([
                    'number' => 1,
                    'title' => $article->title,
                    'summary' => $article->summary,
                    'content' => $article->content,
                    'editor_id' => $authorId,
                    'note' => 'Eerste versie',
                ]);
            } elseif ((int) $article->version === 1) {
                $revision->update([
                    'title' => $article->title,
                    'summary' => $article->summary,
                    'content' => $article->content,
                ]);
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function guides(): array
    {
        return [
            $this->guide('aanmelden', 'Aanmelden', 'aan-de-slag', 'auth.sign-in', '/login', true, 10, 'inloggen wachtwoord', 'Log in op het beheerpaneel.', [
                $this->steps([
                    'Open het beheerpaneel.',
                    'Vul je e-mailadres in bij E-mail.',
                    'Vul je wachtwoord in bij Wachtwoord.',
                    'Kies Inloggen.',
                ]),
                $this->note('Ben je je wachtwoord kwijt, gebruik dan Wachtwoord vergeten? Daarmee vraag je een nieuw wachtwoord aan.'),
            ]),
            $this->guide('collegas-zoeken', "Collega's zoeken op expertise", 'collegas', 'employees.directory', '/admin/employees', true, 20, 'zoeken expertise vaardigheid certificaat synoniem', 'Zoek een collega op naam, vaardigheid, certificaat of synoniem.', [
                $this->paragraph("Open Collega's. Bovenaan de pagina staat Vind de juiste collega."),
                $this->steps([
                    'Typ een naam, vaardigheid, certificaat of synoniem in Zoek op expertise.',
                    'Je kunt ook de zoekbalk bovenin gebruiken: Naam, vaardigheid, certificaat of synoniem.',
                    'Kies een suggestie onder de zoekbalk als die past.',
                    'Open een resultaat met Bekijk profiel.',
                ]),
                $this->note('Een synoniem vindt de bijbehorende vaardigheid of het certificaat, ook als je de officiële naam niet intypt.', 'tip'),
            ]),
            $this->guide('collegas-filteren', "Collega's filteren", 'collegas', 'employees.directory', '/admin/employees', false, 30, 'filter expertise locatie rol actief', 'Beperk de lijst met de filters links.', [
                $this->steps([
                    "Open Collega's.",
                    'Gebruik links de filters Expertise, Certificaten, Locatie, Rol en Actief.',
                    'Een gekozen filter blijft staan tot je Filters resetten kiest.',
                    'Schakel tussen Lijst en Kaarten als je de resultaten anders wilt zien.',
                ]),
            ]),
            $this->guide('profiel-bekijken', 'Een profiel bekijken', 'collegas', 'employees.profile', '/admin/employees', true, 40, 'profiel bekijk profiel', 'Het profiel is alleen-lezen.', [
                $this->steps([
                    "Zoek de collega en kies Bekijk profiel.",
                    'Je ziet rol, locatie, contact, introductie, vaardigheden en certificaten als die zijn ingevuld.',
                    'Een gekleurde label hoort bij die vaardigheid of dat certificaat.',
                    'Heb je rechten om te wijzigen, dan staat Bewerken op het profiel.',
                ]),
                $this->note('Een inactieve collega is niet zichtbaar voor gewone gebruikers.'),
            ]),
            $this->guide('medewerker-bewerken', 'Een medewerker bewerken', 'medewerkersbeheer', 'employees.edit', '/admin/employees', false, 50, 'bewerken opslaan introductie', 'Wijzig een medewerker en keer terug naar het profiel.', [
                $this->steps([
                    'Open het profiel en kies Bewerken.',
                    'Pas de velden aan. De introductie is gewone tekst.',
                    'Sla de wijzigingen op. Je komt terug op het profiel.',
                    'Annuleren laat je op het bewerkingsscherm.',
                ]),
                $this->note('Verlaat je het scherm met onopgeslagen wijzigingen, dan vraagt Hanab om bevestiging.'),
            ], 'update employees'),
            $this->guide('organogram-leiding', 'Het organogram: leiding', 'organogram', 'organogram.management', '/admin/organogram', true, 60, 'organogram leiding rapportage', 'De leidingweergave toont wie aan wie rapporteert.', [
                $this->paragraph('Open Organogram. Kies in de werkbalk de weergave Leiding.'),
                $this->steps([
                    'Een lijn betekent een rapportagelijn.',
                    'Mensen met dezelfde rol kunnen verschillende leidinggevenden hebben.',
                    'Ontbreekt een leidinggevende, dan blijft die plek leeg.',
                    'Gebruik Zoek collega\'s om iemand op de kaart te vinden.',
                    'Passend maken, Inzoomen en Uitzoomen veranderen alleen de weergave.',
                ]),
                $this->note('De weergave Rollen groepeert functies. Dat is geen rapportagelijn.'),
            ]),
            $this->guide('organogram-locatie', 'Het organogram: locatie', 'organogram', 'organogram.location', '/admin/organogram', false, 70, 'organogram locatie', 'De locatieweergave groepeert mensen per vestiging.', [
                $this->steps([
                    'Open Organogram en kies Locatie.',
                    'Elke vestiging staat in een eigen groep.',
                    'Een lijn binnen dezelfde vestiging hoort bij die groep.',
                    'Een lijn tussen twee vestigingen ziet er anders uit, omdat de groepen naast elkaar staan.',
                    'Filter desgewenst op één locatie.',
                ]),
            ]),
            $this->guide('gegevens-importeren', 'Gegevens importeren', 'importeren-en-exporteren', 'employees.import', '/admin/employees', false, 80, 'importeren upload kolommen controleren', 'Lees een bestand in via Acties.', [
                $this->steps([
                    "Open Collega's.",
                    'Kies Acties en daarna Gegevens importeren.',
                    'Uploaden: kies het bestand.',
                    'Kolommen koppelen: accepteer een voorstel, kies een ander veld, of laat een kolom weg. Een verplicht veld heeft één kolom nodig. Elk veld kan één keer worden gebruikt.',
                    'Controleren: bekijk nieuwe, gewijzigde en ongeldige rijen.',
                    'Bevestigen: importeer de rijen die akkoord zijn.',
                    'Controleer daarna de collega\'s in de lijst.',
                ]),
                $this->note('Een voorbeeld is geen import. Pas na bevestigen worden records opgeslagen.', 'warning'),
            ], 'import employees'),
            $this->guide('gegevens-exporteren', 'Gegevens exporteren', 'importeren-en-exporteren', 'employees.export', '/admin/employees', false, 90, 'exporteren download', 'Download de gegevens die je nu gefilterd hebt.', [
                $this->steps([
                    "Open Collega's en stel zoekterm en filters in.",
                    'Kies Acties en daarna Gegevens exporteren.',
                    'Het bestand bevat de velden die voor dit model geëxporteerd mogen worden.',
                    'Een gefilterde export gebruikt de huidige filters en zoekterm, niet alleen de rijen op deze pagina.',
                ]),
            ]),
            $this->guide('vaardigheden-certificaten', 'Vaardigheden en certificaten', 'vaardigheden-en-certificaten', 'skills.certificates', '/admin/skills', false, 100, 'vaardigheid certificaat label', 'Labels op een profiel komen uit de catalogus.', [
                $this->paragraph('Op een profiel zie je vaardigheden en certificaten als gekleurde labels. De kleuren horen bij die vaardigheid of dat certificaat.'),
                $this->steps([
                    'De catalogus voor vaardigheden staat onder Vaardigheid.',
                    'De catalogus voor certificaten staat onder Certificaat.',
                    'Een label op een profiel betekent niet dat iemand die vaardigheid zojuist heeft behaald door een pagina te lezen.',
                ]),
            ]),
            $this->guide('berichten', 'Berichten schrijven en publiceren', 'berichten', 'messages.compose', '/admin/messages', false, 110, 'bericht publiceren concept', 'Een bedrijfsbericht is een mededeling voor collega\'s.', [
                $this->steps([
                    'Open Berichten.',
                    'Kies een nieuw bericht.',
                    'Vul een titel in en schrijf de mededeling.',
                    'Voorbeeld toont de tekst en publiceert niets.',
                    'Concept opslaan bewaart het bericht zonder het te publiceren.',
                    'Publiceren maakt de mededeling beschikbaar voor collega\'s die berichten mogen lezen.',
                ]),
            ]),
            $this->guide('gebruikers', 'Gebruikers en rechten', 'gebruikersbeheer', 'settings.users', '/admin/settings/users', false, 120, 'gebruiker uitnodigen rol', 'Nodig iemand uit en ken een rol toe.', [
                $this->steps([
                    'Open Instellingen en daarna Gebruikers.',
                    'Vul e-mail, naam en rol in en verstuur de uitnodiging.',
                    'Een uitgenodigd account heeft nog geen wachtwoord. Je kunt de uitnodiging opnieuw sturen of intrekken.',
                    'Een actief account kun je uitschakelen.',
                    'De rol bepaalt wat die persoon mag wijzigen. Super-admin blijft beschermd.',
                ]),
                $this->note('Rechten op een hulptekst veranderen niets aan wat de applicatie zelf afdwingt.'),
            ], 'update users'),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @return array<string, mixed>
     */
    private function guide(string $slug, string $title, string $category, string $context, string $path, bool $started, int $sort, string $keywords, string $summary, array $blocks, ?string $audience = null): array
    {
        return [
            'slug' => $slug,
            'title' => $title,
            'summary' => $summary,
            'category' => $category,
            'context_key' => $context,
            'feature_path' => $path,
            'started' => $started,
            'sort_order' => $sort,
            'keywords' => $keywords,
            'audience' => $audience,
            'content' => ['type' => 'doc', 'content' => $this->withHeadings($blocks)],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @return list<array<string, mixed>>
     */
    private function withHeadings(array $blocks): array
    {
        $content = [];
        $steps = false;
        $note = false;
        foreach ($blocks as $block) {
            if (($block['type'] ?? null) === 'stepList' && ! $steps) {
                $content[] = $this->heading('Stappen');
                $steps = true;
            }
            if (($block['type'] ?? null) === 'callout' && ! $note) {
                $content[] = $this->heading('Goed om te weten');
                $note = true;
            }
            $content[] = $block;
        }

        return $content;
    }

    /**
     * @return array<string, mixed>
     */
    private function heading(string $text): array
    {
        return ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => $text]]];
    }

    /**
     * @param  list<string>  $lines
     * @return array<string, mixed>
     */
    private function steps(array $lines): array
    {
        return [
            'type' => 'stepList',
            'content' => array_map(fn (string $line) => [
                'type' => 'listItem',
                'content' => [$this->paragraph($line)],
            ], $lines),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paragraph(string $text): array
    {
        return ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $text]]];
    }

    /**
     * @return array<string, mixed>
     */
    private function note(string $text, string $kind = 'note'): array
    {
        return ['type' => 'callout', 'attrs' => ['kind' => $kind], 'content' => [$this->paragraph($text)]];
    }
}
