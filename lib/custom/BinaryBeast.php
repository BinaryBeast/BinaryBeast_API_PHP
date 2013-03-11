<?php

/**
 * This class is never used, and in fact the file should never even be loaded
 * 
 * It allows developers to define custom code hinting documentaiton, @param and @method values for example
 * 
 * BinaryBeast allows yous to extend the model classes for customizing functionality without editing the core libraries,
 *  and the main BinaryBeast class acts as a factory to return objects using the extended classes - so if you have
 *  an IDE with code hinting, you'll only get code hinting for the core libraries, not the extended one
 * 
 * This is where you can fix that - see the examples below where we define the BinaryBeast factory results for our
 *  two extended classes, LocalTeam and LocalTournament
 * 
 * @property LocalTournament $tournament
 * <b>Alias for {@link BinaryBeast::tournament()}</b><br />
 * <pre>
 *  Returns a new BBTournament object, customized by LocalTeam
 * </pre>
 * 
 * @property LocalTeam $team
 * <b>Alias for {@link BinaryBeast::team()}</b><br />
 *  Returns a new BBTeam object, customized by LocalTeam
 * 
 * @method LocalTournament tournament(string $id)
 *  Returns a new BBTournament object, customized by LocalTeam
 * 
 * @method LocalTeam team(int $user_id)
 *  Returns a new BBTeam object, customized by LocalTeam - based on a local user_id
 */
class CustomBinaryBeast {}

?>