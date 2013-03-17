<?php

/**
 * Test the extended BBTeam class: LocalTeam
 * 
 * and it depends on the LocalTournament override
 * 
 * @group local_tournament
 * @group model
 * @group custom_model
 * @group all
 */
class BBTournamentTest extends BBTest {

    /** @var LocalTeam */
    protected $object;
    /** @var LocalTournament */
    protected $bb;

    /**
     * Create our own custom BinaryBeast class, using a specified
     *  BBConfiguration object, in order to define LocalTournament as
     *      an extension for BBBTournament
     */
    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        $config = new BBConfiguration();
        $config->models_extensions['BBTournament'] = 'LocalTournament';
        $config->models_extensions['BBTeam'] = 'LocalTeam';

        $this->bb = new BinaryBeast($config);
    }
    /**
     * Re-initialize $bb using the default BBConfiguration values
     */
    public static function tearDownAfterClass() {
        parent::tearDownAfterClass();

        $this->bb = new BinaryBeast();
    }

    /**
     * Pseudo test constructor - set object to $bb->tournament,
     *  and verify that it's an instance of LocalTournament, as per
     *  defined in our custom BBConfiguration
     */
    protected function setUp() {
        $this->get_tournament_inactive();

        $this->object = $this->tournament->team();

        $this->assertInstanceOf('BBTeam', $this->object);
        $this->assertInstanceOf('LocalTeam', $this->object);

        parent::setUp();
    }

    
}
