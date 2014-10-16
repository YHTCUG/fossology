<?php
/*
Copyright (C) 2014, Siemens AG

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
version 2 as published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

namespace Fossology\Lib\BusinessRules;

use Fossology\Lib\Dao\AgentsDao;
use Fossology\Lib\Dao\ClearingDao;
use Fossology\Lib\Dao\LicenseDao;
use Fossology\Lib\Data\LicenseMatch;
use Fossology\Lib\Data\LicenseRef;
use Fossology\Lib\Data\Tree\ItemTreeBounds;
use Mockery as M;

class ClearingDecisionEventProcessorTest extends \PHPUnit_Framework_TestCase
{

  /** @var LicenseDao|M\MockInterface */
  private $licenseDao;

  /** @var AgentsDao|M\MockInterface */
  private $agentsDao;

  /** @var ClearingDao|M\MockInterface */
  private $clearingDao;

  /** @var ItemTreeBounds|M\MockInterface */
  private $itemTreeBounds;

  /** @var ClearingDecisionEventProcessor */
  private $clearingDecisionEventProcessor;

  public function setUp()
  {
    $this->licenseDao = M::mock(LicenseDao::classname());
    $this->agentsDao = M::mock(AgentsDao::classname());
    $this->clearingDao = M::mock(ClearingDao::classname());

    $this->itemTreeBounds = M::mock(ItemTreeBounds::classname());

    $this->clearingDecisionEventProcessor = new ClearingDecisionEventProcessor($this->licenseDao, $this->agentsDao, $this->clearingDao);
  }

  public function testGetLatestAgentDetectedLicenses()
  {
    $uploadId = 2;
    list($licenseMatch1, $licenseRef1, $agentRef1) = $this->createLicenseMatch(5, "licA", 23, "nomos", 453, null);
    list($licenseMatch2, $licenseRef2, $agentRef2) = $this->createLicenseMatch(5, "licA", 22, "monk", 665, 95);
    $licenseMatches = array($licenseMatch1, $licenseMatch2);

    $this->itemTreeBounds->shouldReceive('getUploadId')->withNoArgs()->andReturn($uploadId);
    $this->licenseDao->shouldReceive('getAgentFileLicenseMatches')->once()->withArgs(array($this->itemTreeBounds))->andReturn($licenseMatches);
    $this->agentsDao->shouldReceive('getLatestAgentResultForUpload')->once()->withArgs(array($uploadId, array('nomos', 'monk')))->andReturn(
        array(
            'nomos' => 23,
            'monk' => 22
        )
    );

    $latestAgentDetectedLicenses = $this->clearingDecisionEventProcessor->getLatestAgentDetectedLicenses($this->itemTreeBounds);

    assertThat($latestAgentDetectedLicenses, is(array(
        'licA' => array(
            'nomos' => array(
                array('id' => 5, 'licenseRef' => $licenseRef1, 'agentRef' => $agentRef1, 'matchId' => 453, 'percentage' => null)
            ),
            'monk' => array(
                array('id' => 5, 'licenseRef' => $licenseRef2, 'agentRef' => $agentRef2, 'matchId' => 665, 'percentage' => 95)
            )
        )
    )));

  }

  /**
   * @return M\MockInterface
   */
  protected function createLicenseMatch($licenseId, $licenseShortName, $agentId, $agentName, $matchId, $percentage)
  {
    $licenseRef = M::mock(LicenseRef::classname());
    $licenseRef->shouldReceive("getId")->withNoArgs()->andReturn($licenseId);
    $licenseRef->shouldReceive("getShortName")->withNoArgs()->andReturn($licenseShortName);

    $agentRef = M::mock(LicenseRef::classname());
    $agentRef->shouldReceive("getAgentId")->withNoArgs()->andReturn($agentId);
    $agentRef->shouldReceive("getAgentName")->withNoArgs()->andReturn($agentName);
    $agentRef->shouldReceive("getAgentName")->withNoArgs()->andReturn($agentName);

    $licenseMatch = M::mock(LicenseMatch::classname());
    $licenseMatch->shouldReceive("getLicenseRef")->withNoArgs()->andReturn($licenseRef);
    $licenseMatch->shouldReceive("getAgentRef")->withNoArgs()->andReturn($agentRef);
    $licenseMatch->shouldReceive("getLicenseFileId")->withNoArgs()->andReturn($matchId);
    $licenseMatch->shouldReceive("getPercentage")->withNoArgs()->andReturn($percentage);
    return array($licenseMatch, $licenseRef, $agentRef);
  }

}

 