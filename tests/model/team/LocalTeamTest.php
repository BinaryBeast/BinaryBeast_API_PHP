<?php

/**
 * Test the extended BBTeam class: LocalTeam
 *  and it depends on the LocalTournament override
 * 
 * @todo Test extending the BBTeam model
 * 
 * @group local_team
 * @group model
 * @group custom_model
 * @group all
 */
class LocalTeamTest extends BBTest {

    /** @var LocalTeam */
    protected $object;
    /** @var LocalTournament */
    protected $tournament;

    /**
     * Create our own custom BinaryBeast class, using a specified
     *  BBConfiguration object, in order to define LocalTournament as
     *      an extension for BBBTournament
     */
    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        $config = new BBConfiguration();
        $config->models_extensions['BBTournament']  = 'LocalTournament';
        $config->models_extensions['BBTeam']        = 'LocalTeam';
        self::$bb_static = new BinaryBeast($config);
        self::$bb_static->disable_ssl_verification();
    }
    /**
     * Pseudo test constructor - set object to $bb->tournament,
     *  and verify that it's an instance of LocalTournament, as per
     *  defined in our custom BBConfiguration
     */
    protected function setUp() {
        $this->bb = &self::$bb_static;
        $this->get_tournament_inactive();
        
        $this->object = $this->bb->team();

        $this->assertInstanceOf('BBTeam', $this->object);
        $this->assertInstanceOf('LocalTeam', $this->object);

        parent::setUp();
    }
    /**
     * Re-instantiate $bb as a normal instance without the custom $config
     */
    public static function tearDownAfterClass() {
        parent::tearDownAfterClass();
        self::$bb_static = new BinaryBeast();
        self::$bb_static->disable_ssl_verification();
    }

}

?>