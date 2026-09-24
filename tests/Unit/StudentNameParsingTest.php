<?php

namespace Tests\Unit;

use App\Models\Student;
use PHPUnit\Framework\TestCase;

class StudentNameParsingTest extends TestCase
{
    public function test_full_name_uses_saved_name_fields_without_guessing_middle_name(): void
    {
        $student = new Student();
        $student->first_name = 'Bryll Benny Rose';
        $student->middle_name = null;
        $student->last_name = 'Basil';

        $this->assertSame('Bryll Benny Rose Basil', $student->full_name);

        $student->first_name = 'Ria May';
        $student->middle_name = 'Aredidon';
        $student->last_name = 'Astronomo';

        $this->assertSame('Ria May Aredidon Astronomo', $student->full_name);
    }
}
