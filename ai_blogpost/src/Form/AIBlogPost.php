<?php

namespace Drupal\ai_blogpost\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ai_agents\Task\Task;
use Drupal\node\Entity\Node;
use Drupal\ai_agents\PluginInterfaces\AiAgentInterface;
use Drupal\ai_agents\PluginInterfaces\ConfigAiAgentInterface;

class AIBlogPost extends FormBase {

  public function getFormId(): string {
    return 'ai_blog_post_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Blog Prompt'),
      '#description' => $this->t('Enter a topic or prompt for the blog post.'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $prompt = $form_state->getValue('prompt');

    try {
     
      $plugin_id = 'blog_generator';
      $plugin_manager = \Drupal::service('plugin.manager.ai_agents');
      $agent = $plugin_manager->createInstance($plugin_id);
      $task = new Task($prompt);
      $agent->setTask($task);
      $agent->setRunnerId('ai_blog_form');
      $provider_manager = \Drupal::service('ai.provider');
      $provider_instance = $provider_manager->createInstance('openai'); // Replace with your actual provider.
      $provider_instance->setChatSystemRole('You are a helpful assistant.');
      $agent->setAiProvider($provider_instance);
      $agent->setModelName('gpt-4'); // Replace if different.
      $agent->setAiConfiguration([]);
      $agent->setCreateDirectly(TRUE);

      if ($agent instanceof ConfigAiAgentInterface) {
        $agent->setTokenContexts([]);
      }

      // Step 4: Determine solvability and get response.
      $solution_type = $agent->determineSolvability();
      $response = NULL;

      if ($solution_type === AiAgentInterface::JOB_SHOULD_ANSWER_QUESTION) {
        $response = $agent->answerQuestion();
      }
      elseif ($solution_type === AiAgentInterface::JOB_SOLVABLE) {
        $response = $agent->solve();
      }
      else {
        $this->messenger()->addError($this->t('AI Agent cannot solve the task.'));
        return;
      }

// Step 5: Normalize and parse response.
if (is_string($response)) {
  // Step 1: Clean up non-printable/control characters.
  $cleaned = preg_replace('/[[:cntrl:]]/', '', $response); // removes all control chars
  $cleaned = trim($cleaned);

  // Step 2: Attempt to decode
  $decoded = json_decode($cleaned, TRUE);

  if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
    $response = $decoded;
  }
  else {
    // Step 3: Fallback — try to extract valid JSON object from a larger string
    preg_match('/\{.*\}/s', $cleaned, $matches);
    if (!empty($matches[0])) {
      $fallback_clean = preg_replace('/[[:cntrl:]]/', '', $matches[0]);
      $decoded = json_decode($fallback_clean, TRUE);

      if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $response = $decoded;
      }
      else {
        \Drupal::logger('ai_blogpost')->error('Fallback JSON decode failed: @msg', ['@msg' => json_last_error_msg()]);
        $this->messenger()->addError($this->t('Could not parse AI response (fallback failed): @msg', ['@msg' => json_last_error_msg()]));
        return;
      }
    }
    else {
      \Drupal::logger('ai_blogpost')->error('No JSON found in response: @response', ['@response' => $cleaned]);
      $this->messenger()->addError($this->t('Invalid AI response format. No JSON found.'));
      return;
    }
  }
}


if($response)
{

  $title = $response['title'] ;
  $body = $response['body'] ;

if (empty($body)) {
  $this->messenger()->addError($this->t('No content generated.'));
  return;
}

  $node = Node::create([
        'type' => 'blog_post', // Make sure this content type exists.
        'title' => $title,
        'field_body' => [ // Adjust this field name if different.
          'value' => $body,
          'format' => 'full_html',
        ],
        'status' => 0, // Draft.
      ]);
      $node->save();
      $this->messenger()->addStatus($this->t('Draft blog post created: %title', ['%title' => $title]));

}
else {
  \Drupal::logger('ai_blogpost')->error('AI response is empty or invalid.');
  $this->messenger()->addError($this->t('AI response is empty or invalid.'));
  return;
}

      // Step 6: Debugging output.
      \Drupal::logger('ai_blogpost')->info('AI Response: @response', ['@response' => print_r($response, TRUE)]);
   
      // Step 7: Save blog node as draft.
      

     // $this->messenger()->addStatus($this->t('Draft blog post created: %title', ['%title' => $title]));
    }
    catch (\Throwable $e) {
      \Drupal::logger('ai_blogpost')->error('Error: @error', ['@error' => $e->getMessage()]);
      $this->messenger()->addError($this->t('Error: @error', ['@error' => $e->getMessage()]));
    }
  }

}
