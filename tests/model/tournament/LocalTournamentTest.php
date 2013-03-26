<?php

/**
 * Test the extended BBTournament class: LocalTournament
 * 
 * @todo Test extending the BBTournament model
 * 
 * @group local_tournament
 * @group model
 * @group custom_model
 * @group all
 */
class LocalTournamentTest extends BBTest {

    /** @var LocalTournament */
    protected $object;

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
        
        $this->object = $this->bb->tournament();

        $this->assertInstanceOf('BBTournament', $this->object);
        $this->assertInstanceOf('LocalTournament', $this->object);

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