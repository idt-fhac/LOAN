<?php

/*
 * Wer betreibt diese Instanz.
 *
 * Die Werte erscheinen auf der Anmeldeseite, in der Navigationsleiste und im
 * Footer. Leer lassen blendet die jeweilige Angabe aus - so laeuft LOAN ohne
 * Aenderung am Code auch fuer mehrere Fachbereiche oder hochschulweit.
 *
 * Die Vorgaben passen zum ersten Einsatzort. Ein anderer Fachbereich setzt in
 * der .env nur:
 *
 *     LOAN_UNIT="Fachbereich 05"
 *     LOAN_UNIT_SHORT="FB05"
 *     LOAN_CAMPUS="Campus Eupener Strasse"
 *
 * Hochschulweit: die drei Werte auf "" setzen.
 */
return [

    'organisation' => env('LOAN_ORGANISATION', 'FH Aachen'),

    // Institutioneller Zusatz, im Footer hinter der Organisation.
    'organisation_suffix' => env('LOAN_ORGANISATION_SUFFIX', 'University of Applied Sciences'),

    // Ausgeschrieben, z.B. "Fachbereich 09".
    'unit' => env('LOAN_UNIT', 'Fachbereich 09'),

    // Kurzform fuer die schmale Navigationsleiste, z.B. "FB09".
    'unit_short' => env('LOAN_UNIT_SHORT', 'FB09'),

    'campus' => env('LOAN_CAMPUS', 'Campus Jülich'),

];
