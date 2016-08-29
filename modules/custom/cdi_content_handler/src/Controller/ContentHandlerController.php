<?php

namespace Drupal\cdi_content_handler\Controller;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\node\Entity\Node;

class ContentHandlerController {
  const BASE_URL = 'http://ec2-52-38-26-42.us-west-2.compute.amazonaws.com:8080';

  //Handles theme page route
  public function vocabulary($lexicon, $theme){
    $vocabulary_id = \Drupal::request()->query->get('id');
    $vocabulary_raw_encoded = rawurlencode($vocabulary_id);
    $object = $this->makeRequest("/vocabulary/{$lexicon}/{$theme}/{$vocabulary_raw_encoded}");
    $counts = $this->makeRequest(self::BASE_URL . "/search.json?q=" . $vocabulary_raw_encoded . "&type=all&format=detailed&count_only=1", true);

    $counts = array_filter($counts, function($el) {
      return in_array($el->type, array(
        "report", "chapter", "finding", "book",
        "article", "webpage", "image", "figure",
        "dataset",
        "toolkit", "case_study",
        "organization", "person","vocabulary"
      ));
    });

    $facets = [
      "depth_assessments" => ["report", "chapter", "finding", "book"],
      "short_assessments" => ["article", "webpage"],
      "images" => ["image", "figure"],
      "data" => ["dataset"],
      "resiliency"=> ["toolkit", "case_study"],
      "others" => ["organization", "person", "vocabulary"],
    ];

    $count_depth_assessments = 0;
    $count_short_assessments = 0;
    $count_images = 0;
    $count_data = 0;
    $count_resiliency = 0;
    $count_other = 0;
    $count_total = 0;
    foreach($counts as $count){
      if(in_array($count->type, $facets['depth_assessments'])){
        $count_depth_assessments += $count->results_count;
      }
      else if(in_array($count->type, $facets['short_assessments'])){
        $count_short_assessments += $count->results_count;
      }
      else if(in_array($count->type, $facets['images'])){
        $count_images += $count->results_count;
      }
      else if(in_array($count->type, $facets['data'])){
        $count_data += $count->results_count;
      }
      else if(in_array($count->type, $facets['resiliency'])){
        $count_resiliency += $count->results_count;
      }
      else{
        $count_other += $count->results_count;
      }
      $count_total += $count->results_count;
    }

    if($count_total > 0) {
      $counts_to_return = [
        (object)[
          "label" => "In-Depth Assessments",
          "type" =>  "in_depth_assessments",
          "results_count" => $count_depth_assessments
        ],
        (object)[
          "label" => "Short Assessments",
          "type" =>  "short_assessments",
          "results_count" => $count_short_assessments
        ],
        (object)[
          "label" => "Images",
          "type" =>  "images",
          "results_count" => $count_images
        ],
        (object)[
          "label" => "Datasets",
          "type" =>  "data",
          "results_count" => $count_data
        ],
        (object)[
          "label" => "Resiliency",
          "type" =>  "resiliency",
          "results_count" => $count_resiliency
        ],
        (object)[
          "label" => "Others",
          "type" =>  "others",
          "results_count" => $count_other
        ],
      ];
    }

    //Get the popular resource related to this node
    $nid = \Drupal::entityQuery('node')
      ->condition('type', 'theme')
      ->condition("field_theme_page.uri", "internal:/vocabulary/{$lexicon}/{$theme}?id={$vocabulary_raw_encoded}") //This should be unique, but we will get first one regardless
      ->execute();

    if(isset($nid)) {
      $term = Node::load(current($nid));
      if(isset($term) && $term->field_popular_resource_type->value != "") {
        $popular_resource = (object)[
          'type' => $term->field_popular_resource_type->value,
          'title' => $term->field_popular_resource_title->value,
          'summary' => $term->field_popular_resource_summary->value,
          'url' => $term->field_popular_resource_url->first()->getUrl()
        ];
      }
    }

    $object->description = preg_replace('/<tbib>[\s\S]+?<\/tbib>/', '', $object->description);

    return array(
      '#name' => 'Content Handler',
      '#theme' => 'cdi_term_handler',
      '#entity' => $object,
      '#counts' => $counts_to_return,
      '#popular_resource' => isset($popular_resource) ? $popular_resource : null,
      '#cache' => array(
        'max-age' => 0
      ),
      '#attached' => [
        'library' => ['cdi_content_handler/cdi_content_handler']
      ]
    );

  }

  private function makeRequest($URI, $full_url = false){
    try {
      $client = \Drupal::httpClient();
      if ($full_url)
        $response = $client->request('GET', $URI, ['http_errors' => false]);
      else
        $response = $client->request('GET', self::BASE_URL . $URI . '.json', ['http_errors' => false]);

      if($response->getStatusCode() == 404){
        throw new NotFoundHttpException();
      }
      return json_decode($response->getBody());

    }
    catch(RequestException $e){
    }


  }

  private function getRenderArray($object){
    if($object->attrs){
      $object->attrs = (array) $object->attrs;
    }

    if($object->description) {
      $object->description = preg_replace('/<tbib>[\s\S]+?<\/tbib>/', '', $object->description);
      $object->description = preg_replace('/<sup>[\s\S]+?<\/sup>/', '', $object->description);
    }
    if($object->evidence) {
      $object->evidence = preg_replace('/<tbib>[\s\S]+?<\/tbib>/', '', $object->evidence);
      $object->evidence = preg_replace('/<sup>[\s\S]+?<\/sup>/', '', $object->evidence);
    }
    if($object->uncertainties) {
      $object->uncertainties = preg_replace('/<tbib>[\s\S]+?<\/tbib>/', '', $object->uncertainties);
      $object->uncertainties = preg_replace('/<sup>[\s\S]+?<\/sup>/', '', $object->uncertainties);
    }
    return array(
      '#name' => 'Content Handler',
      '#theme' => 'cdi_content_handler',
      '#entity' => $object,
      '#contributors' => $this->arrangeContributorRoleTypes($object),
      '#citations' => $this->filterCitations($object->parents),
      '#baseurl' => self::BASE_URL,
      '#attached' => [
        'library' => ['cdi_content_handler/cdi_content_handler']
      ]
    );
  }

  private function filterCitations($parents){
    $toReturn = array();
    foreach($parents as $parent){
      if($parent->relationship === 'cito:isCitedBy'){
        $toReturn[] = $parent;
      }
    }
    return $toReturn;
  }


  private function arrangeContributorRoleTypes($contributors){
    if($contributors->contributors == null){
      return array();
    }

    $role_types = $this->makeRequest(self::BASE_URL . "/role_type.json?all=true", true);
    $organizedContributors = array();
    if($contributors->type === 'person' || $contributors->type === 'organization'){
      $type_text = t("Contributions");
    }
    else{
      $type_text = t("Contributors");
    }

    $organizedContributors["type"] = $type_text;
    foreach($role_types as $role_type){
      foreach($contributors->contributors as $contributor){

        foreach($contributor->publications as $publication){
            $exploded = explode("/article/", $publication->uri);
            if(sizeof($exploded) > 1){
                $publication->uri = "/article?id=" . $exploded[1];
            }
        }

        if($contributor->role_type_identifier === $role_type->identifier){
          if(!isset($organizedContributors[$role_type->identifier])) {
            $organizedContributors[$role_type->identifier] = array();
            $organizedContributors[$role_type->identifier]['label'] = $role_type->label;
          }

          $organizedContributors[$role_type->identifier]['contributors'][] = $contributor;
        }
      }
    }

    return $organizedContributors;
  }


  public function content($type, $id){
    return $this->getRenderArray($this->makeRequest("/{$type}/{$id}"));
  }

  public function article(){
    $article_id = \Drupal::request()->query->get('id');
    $article_id = urldecode($article_id);
    $render_array = $this->getRenderArray($this->makeRequest("/article/{$article_id}"));
    $render_array['#cache']['max-age'] = 0;
    return $render_array;
  }

  public function report($report_id){
    $render_array = $this->getRenderArray($this->makeRequest("/report/{$report_id}"));
    $render_array['#chapters'] = $this->makeRequest("/report/{$report_id}/chapter");
    return $render_array;
  }

  public function chapterFinding($chapter_id, $finding_id){
    return $this->getRenderArray($this->makeRequest('/chapter/' . $chapter_id . '/finding/'. $finding_id));
  }

  public function reportFinding($report_id, $finding_id){
    return $this->getRenderArray($this->makeRequest('/report/' . $report_id . '/finding/'. $finding_id ));
  }

  public function chapter($report_id, $chapter_id){
    return $this->getRenderArray($this->makeRequest('/report/' . $report_id . '/chapter/'. $chapter_id ));
  }
  public function reportChapterFinding($report_id, $chapter_id, $finding_id){
    return $this->getRenderArray($this->makeRequest('/report/' . $report_id . '/chapter/'. $chapter_id . '/finding/' . $finding_id));
  }

  public function reportChapterFigure($report_id, $chapter_id, $figure_id){
    return $this->getRenderArray($this->makeRequest('/report/' . $report_id . '/chapter/'. $chapter_id . '/figure/' . $figure_id));
  }

  public function reportFigure($report_id, $figure_id){
    return $this->getRenderArray($this->makeRequest('/report/' . $report_id . '/figure/' . $figure_id));
  }

}